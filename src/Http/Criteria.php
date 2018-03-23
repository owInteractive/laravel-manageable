<?php

namespace Ow\Manageable\Http;

use Ow\Manageable\Contracts\RepositoryContract;
use Ow\Manageable\Contracts\CriteriaContract;
use Ow\Manageable\Contracts\Manageable;

// use Illuminate\Database\Eloquent\Builder;
// use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class Criteria implements CriteriaContract
{
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function apply($entity, RepositoryContract $repository = null)
    {
        // $orderBy = $this->request->get(config('repository.criteria.params.orderBy', 'orderBy'), null);
        // $sortedBy = $this->request->get(config('repository.criteria.params.sortedBy', 'sortedBy'), 'asc');
        // $searchJoin = $this->request->get(config('repository.criteria.params.searchJoin', 'searchJoin'), null);
        // $sortedBy = !empty($sortedBy) ? $sortedBy : 'asc';

        $entity = $this->parseSearch($entity);
        $entity = $this->parseFilter($entity);
        $entity = $this->parseWith($entity);
        $entity = $this->parseOrderBy($entity);

        return $entity;
    }

    protected function parseFilter($entity)
    {
        $filter = $this->request->get(config('manageable.criteria.params.filter', '_filter'), null);

        if (!empty($filter)) {
            if (is_string($filter)) {
                $filter = explode(';', $filter);
            }

            $entity = $entity->select($filter);
        }

        return $entity;
    }

    protected function parseWith($entity)
    {
        $with = $this->request->get(config('manageable.criteria.params.with', '_with'), null);

        if (!empty($with)) {
            $with = explode(';', $with);
            $entity = $entity->with($with);
        }

        return $entity;
    }

    protected function parseOrderBy($entity)
    {
        $order_by = $this->request->get(config('manageable.criteria.params.order', '_order'), null);

        if (!empty($orderBy)) {
            $entity = $entity->orderBy($order_by);
        }

        return $entity;
    }

    protected function parseSearch($entity)
    {
        $fields_searchable = $entity->getSearchableFields();

        $search = $this->request->get(
            config('manageable.criteria.params.search', '_search'),
            null
        );

        if ($search && is_array($fields_searchable) && count($fields_searchable)) {
            $search_fields = $this->request->get(
                config('manageable.criteria.params.search_fields', '_search_fields'),
                null
            );

            $search_fields = is_array($search_fields) || is_null($search_fields)
                ? $search_fields
                : explode(';', $search_fields);

            $fields = $this->parserFieldsSearch($fields_searchable, $search_fields);

            $data = $this->parserSearchData($search);
            $search = $this->parserSearchValue($search);

            $first_field = true;
            $force_and = false; //strtolower($searchJoin) === 'and';

            $entity = $entity->where(
                function ($query) use ($fields, $search, $data, $first_field, $force_and) {
                    foreach ($fields as $field => $condition) {
                        if (is_numeric($field)) {
                            $field = $condition;
                            $condition = "=";
                        }

                        $value = null;

                        $condition = trim(strtolower($condition));

                        if (isset($data[$field])) {
                            $value = ($condition == "like" || $condition == "ilike")
                                ? "%{$data[$field]}%"
                                : $data[$field];
                        } else {
                            if (!is_null($search)) {
                                $value = ($condition == "like" || $condition == "ilike")
                                    ? "%{$search}%"
                                    : $search;
                            }
                        }

                        $relation = null;

                        if (stripos($field, '.')) {
                            $explode = explode('.', $field);
                            $field = array_pop($explode);
                            $relation = implode('.', $explode);
                        }

                        $table = $query->getModel()->getTable();

                        if (!is_null($value)) {
                            $method = $first_field || $force_and ? 'where' : 'orWhere';
                            $is_in = strtolower($condition) === 'in';


                            if ($is_in && is_string($value)) {
                                $value = explode(',', $value);
                            }

                            if (!is_null($relation)) {
                                $method .= 'Has';
                                $query->{$method}(
                                    $relation,
                                    function ($query) use ($field, $condition, $value, $is_in) {
                                        if ($is_in) {
                                            $query->whereIn($field, $value);
                                            return;
                                        }
                                        $query->where($field, $condition, $value);
                                    }
                                );
                            } else {
                                if ($is_in) {
                                    $method .= 'In';
                                    $query->{$method}($table . '.' . $field, $value);
                                } else {
                                    $query->{$method}($table . '.' . $field, $condition, $value);
                                }
                            }

                            $first_field = false;
                        }
                    }
                }
            );
        }

        return $entity;
    }

    protected function parserSearchData($search)
    {
        $search_data = [];

        if (stripos($search, ':')) {
            $fields = explode(';', $search);

            foreach ($fields as $row) {
                try {
                    list($field, $value) = explode(':', $row);
                    $search_data[$field] = $value;
                } catch (\Exception $e) {
                    // Surround offset error
                }
            }
        }

        return $search_data;
    }

    protected function parserSearchValue($search)
    {
        if (stripos($search, ';') || stripos($search, ':')) {
            $values = explode(';', $search);

            foreach ($values as $value) {
                $s = explode(':', $value);
                if (count($s) == 1) {
                    return $s[0];
                }
            }

            return null;
        }

        return $search;
    }

    protected function parserFieldsSearch(array $fields = [], array $search_fields = null)
    {
        if (!is_null($search_fields) && count($search_fields)) {
            $conditions = config('repository.criteria.conditions', ['=', 'like']);
            $original_fields = $fields;
            $fields = [];

            foreach ($search_fields as $index => $field) {
                $field_parts = explode(':', $field); // [key, values]
                $tmp_index = array_search($field_parts[0], $original_fields);

                if (count($field_parts) == 2) {
                    if (in_array($field_parts[1], $conditions)) {
                        unset($original_fields[$tmp_index]);

                        $field = $field_parts[0];
                        $condition = $field_parts[1];
                        $original_fields[$field] = $condition;
                        $search_fields[$index] = $field;
                    }
                }
            }

            foreach ($original_fields as $field => $condition) {
                if (is_numeric($field)) {
                    $field = $condition;
                    $condition = "=";
                }
                if (in_array($field, $search_fields)) {
                    $fields[$field] = $condition;
                }
            }

            if (count($fields) == 0) {
                throw new \Exception(trans('manageable::criteria.fields_not_accepted', [
                    'field' => implode(',', $search_fields)
                ]));
            }
        }

        return $fields;
    }
}
