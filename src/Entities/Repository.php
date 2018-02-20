<?php

namespace Ow\Manageable\Entities;

use Ow\Manageable\Contracts\RepositoryContract;
use Ow\Manageable\Contracts\CriteriaContract;
use Ow\Manageable\Contracts\Manageable;
use Illuminate\Http\Request;

use Ow\Manageable\Entities\Traits\HasEntity;

class Repository implements RepositoryContract
{
    use HasEntity;

    protected $collection;

    protected $entity;

    protected $criterias = [];

    protected $skip_criteria  = false;

    public function __construct(Manageable $entity)
    {
        $this->entity = $entity;
        $this->entity_class = get_class($entity);
        $this->collection = collect([]);
    }

    public function get()
    {
        $this->applyCriterias();

        $this->collection = $this->entity->get();

        $this->resetEntity();

        return  $this->collection;
    }

    public function with($relations)
    {
        $this->entity = $this->entity->with($relations);

        return $this;
    }

    public function paginate($limit = null, $columns = ['*'], $method = "paginate")
    {
        $this->applyCriterias();
        // $this->applyScope();

        $limit = is_null($limit) ? config('manageable.pagination.limit', 15) : $limit;

        $this->collection = $this->entity->{$method}($limit, $columns);

        $this->collection->appends(app('request')->query());

        $this->resetEntity();

        // $this->setPresenters() //order matters

        return $this->collection;
    }

    public function find($id, $field = null)
    {
        $instance = $this->findWithoutFail($id, $field);

        if ($instance == null) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException;
        }

        $this->collection = collect([$instance]);

        return $instance;
    }

    public function findWithoutFail($id, $field = null)
    {
        $this->applyCriterias();

        // $this->applyScope();

        try {
            if ($field !== null) {
                $instance = $this->entity->where($field, $id)->first();
            } else {
                $instance = $this->entity->find((int) $id);
            }

            if (!empty($instance)) {
                $this->collection = collect([$instance]);
            }
        } catch (\Exception $e) {
            if (config('app.debug')) {
                throw $e;
            }

            $instance = null;
        }

        $this->resetEntity();

        // $this->setPresenters() //order matters

        return $instance;
    }

    /**
     * Criteria Handling
     */
    public function resetCriterias()
    {
        return $this->criterias = [];
    }

    public function getCriterias()
    {
        return $this->criterias;
    }

    public function pushCriteria(CriteriaContract $criteria)
    {
        array_push($this->criterias, $criteria);

        return $this;
    }

    public function unshiftCriteria(CriteriaContract $criteria)
    {
        array_unshift($this->criterias, $criteria);

        return $this;
    }

    public function withoutCriteria()
    {
        $this->skip_criteria = true;
    
        return $this;
    }


    public function withCriteria()
    {
        $this->skip_criteria = false;
    
        return $this;
    }

    protected function applyCriterias()
    {
        $criterias = $this->getCriterias();

        if ($criterias && !$this->skip_criteria) {
            foreach ($criterias as $criteria) {
                if ($criteria instanceof CriteriaContract) {
                    $this->entity = $criteria->apply($this->entity, $this);
                }
            }
        }

        return $this;
    }
}
