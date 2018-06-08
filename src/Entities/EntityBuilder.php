<?php

namespace Ow\Manageable\Entities;

use Illuminate\Database\Eloquent\Relations;
use Illuminate\Database\Eloquent\Model;
use Exception;
use Log;

use Ow\Manageable\Entities\Traits\HasEntity;

class EntityBuilder
{
    use HasEntity;

    protected $entity;

    public function __construct(Model $entity)
    {
        $this->entity = $entity;
        $this->entity_class = get_class($entity);
    }

    public function create(array $attributes)
    {
        $entity = $this->entity->newInstance($attributes);
        $entity->save();

        $this->resetEntity();

        $entity->postProcess($attributes);

        $event = EventFactory::build($entity, $attributes, 'stored');
        if ($event !== null) {
            event($event);
        }

        $entity = $this->updateRelations($entity, $attributes);

        $entity->save();

        return $entity;
    }

    public function update($id, array $attributes)
    {
        // Have to skip presenter to get a model not some data
        $entity = $this->entity->find($id);

        if ($entity === null) {
            $entity_class = get_class($entity);
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException(
                "The {$entity_class} {$id} was not found"
            );
        }

        $entity->fill($attributes);

        $entity->save();

        $this->resetEntity();

        $entity->postProcess($attributes);

        $event = EventFactory::build($entity, $attributes, 'updated');
        if ($event !== null) {
            event($event);
        }

        $entity = $this->updateRelations($entity, $attributes);

        $entity->save();

        return $entity;
    }

    public function updateRelations($model, $attributes)
    {
        foreach ($attributes as $key => $val) {
            if (isset($model) && method_exists($model, $key)) {
                $morph = false;

                $relation = $model->$key();
                $relation_class = Relations\Relation::class;

                if ($relation instanceof $relation_class) {
                    $method_class = get_class($model->$key($key));

                    switch ($method_class) {
                        case Relations\MorphToMany::class:
                            $morph = true;
                            // Same behaviour for the BelongsToMany
                            // no break
                        case Relations\BelongsToMany::class:
                            $new_values = array_get($attributes, $key, []);

                            if (empty($new_values)) {
                                $new_values = [];
                            }

                            if (array_search('', $new_values) !== false) {
                                unset($new_values[array_search('', $new_values)]);
                            }

                            $sync_values = [];
                            $related_key = $relation->getRelated()->getKeyName();

                            // Not used @deprecate
                            if (!empty($morph)) {
                                $model_type = $relation->getMorphType();
                            }

                            foreach (array_values($new_values) as $value) {
                                if (is_array($value)) {
                                    if (!empty($value[$related_key])) {
                                        $sync_values[$value[$related_key]] = [];

                                        if (!empty($value['pivot'])) {
                                            $sync_values[$value[$related_key]] = $value['pivot'];
                                        }
                                    } else {
                                        // If no id is provided we will try to create a new value
                                        try {
                                            $related_builder = new EntityBuilder($model->$key()->getRelated());
                                            $new_related = $related_builder->create($value);
                                            $sync_values[$new_related->id] = $value['pivot'] ?? [];
                                        } catch (\Exception $e) {
                                            Log::error('Unable to create ManyToMany entity, please validate the data');
                                        }
                                    }
                                } elseif (is_numeric($value)) {
                                    $sync_values[$value] = [];
                                }
                            }

                            $model->$key()->sync($sync_values);

                            break;
                        case Relations\BelongsTo::class:
                            $model_key = $model->$key()->getForeignKey();
                            $new_value = array_get($attributes, $key, null);

                            switch (gettype($new_value)) {
                                case 'array':
                                    $owner_key = $model->$key()->getOwnerKey();
                                    $related_id = array_get($new_value, $owner_key, null);

                                    if (!$related_id && (($related = $model->$key) !== null)) {
                                        $related_id = $related->getKey();
                                    }

                                    if ($related_id) {
                                        $related_builder = new EntityBuilder($model->$key()->getRelated());
                                        $updated_related = $related_builder->update((int) $related_id, $new_value);
                                    }

                                    $model->$model_key = $related_id;

                                    break;
                                case 'integer':
                                case 'string':
                                default:
                                    $new_value = $new_value == '' ? null : $new_value;
                                    $model->$model_key = $new_value;
                            }

                            break;
                        case Relations\HasOneOrMany::class:
                            // Not used
                            break;
                        /**
                        * To use the HasMany the request must send eiher the ids with [ id1, id2]
                        * or the elements to be updated: [[hasMany1], [hasMany2], [hasMany3]]
                         */
                        case Relations\MorphOne::class:
                            $morph = true;

                        // Falls throught HasOne on MorphOn
                        // no break
                        case Relations\HasOne::class:
                            $new_values = array_get($attributes, $key, []);

                            // @discuss is this useful?
                            // if (array_search('', $new_values) !== false) {
                            //     unset($new_values[array_search('', $new_values)]);
                            // }

                            $model_key = $relation->getForeignKeyName();
                            if (!empty($morph)) {
                                $model_type = $relation->getMorphType();
                            }

                            if (!empty($new_values)) {
                                $related = get_class($model->$key()->getRelated());
                                $related_model = new $related;

                                switch (gettype($new_values)) {
                                    case 'integer':
                                    case 'string':
                                        $related_instance = $related::find((int) $new_values);

                                        if (!empty($related_instance)) {
                                            $related_instance->$model_key = $model->id;
                                            $related_instance->save();
                                        }

                                        break;
                                    case 'array':
                                        $primary_key = $related_model->getKeyName();
                                        list($tmp, $parent_key) = explode(
                                            '.',
                                            $relation->getQualifiedParentKeyName()
                                        );

                                        $related_builder = new EntityBuilder($related_model);

                                        $new_values[$model_key] = $model->{$parent_key};
                                        if (!empty($morph)) {
                                            $new_values[$model_type] = $this->entity_class;
                                        }

                                        $has_primary_key = array_search(
                                            $primary_key,
                                            array_keys($new_values)
                                        );

                                        if ($has_primary_key !== false && !empty($new_values[$primary_key])) {
                                            $related_builder->update((int) $new_values[$primary_key], $new_values);
                                        } else {
                                            $related_builder->create($new_values);
                                        }

                                        break;
                                    default:
                                        // do nothing
                                        break;
                                }
                            }

                            break;

                        case Relations\MorphMany::class:
                            $morph = true;
                            // Set flag as morph and goes the process for the HasMany

                            // no break
                        case Relations\HasMany::class:
                            $new_values = array_get($attributes, $key, []);

                            if ($new_values == null) {
                                $new_values = [];
                            }

                            if (array_search('', $new_values) !== false) {
                                unset($new_values[array_search('', $new_values)]);
                            }

                            $model_key= $model->$key($key)->getForeignKeyName();
                            if ($morph === true) {
                                $model_type = $relation->getMorphType();
                            }

                            // Removes the relationships that are not present is the sent array
                            // foreach ($model->$key as $rel) {
                            //     if (!in_array($rel->id, $new_values)) {
                            //         try {
                            //             $rel->$model_key = null;
                            //             $rel->save();
                            //         } catch (\Exception $e) {
                            //             // @todo
                            //         }

                            //     }
                            //     unset($new_values[array_search($rel->id, $new_values)]);
                            // }

                            if (count($new_values) > 0) {
                                $related = get_class($model->$key()->getRelated());
                                $related_model = new $related;

                                foreach ($new_values as $val) {
                                    switch (gettype($val)) {
                                        case 'integer':
                                        case 'string':
                                            $related_instance = $related::find((int) $val);

                                            if (!empty($related_instance)) {
                                                $related_instance->$model_key = $model->id;
                                                $related_instance->save();
                                            }

                                            break;

                                        case 'array':
                                            $primary_key = $related_model->getKeyName();
                                            list($tmp, $parent_key) = explode(
                                                '.',
                                                $relation->getQualifiedParentKeyName()
                                            );

                                            $has_primary_key = array_search(
                                                $related_model->getKeyName(),
                                                array_keys($val)
                                            );

                                            $val[$model_key] = $model->{$parent_key};
                                            if (!empty($morph)) {
                                                $val[$model_type] = $this->entity_class;
                                            }

                                            $related_builder = new EntityBuilder($related_model);

                                            // dump('hasmany array');
                                            // dump($val);
                                            // dd($has_primary_key !== false && !empty($val[$primary_key]));

                                            if ($has_primary_key !== false && !empty($val[$primary_key])) {
                                                $related_builder->update((int) $val[$primary_key], $val);
                                            } else {
                                                $related_builder->create($val);
                                            }

                                            break;
                                        default:
                                            // do nothing.
                                            break;
                                    }
                                }
                            }
                            break;
                        default:
                            Log::error("Relationship not specified to Repository: {$method_class} for", [
                               'model' => $this->entity_class,
                               'attributes' => $attributes,
                               'key' => $key
                            ]);

                            // do nothing.
                            break;
                    }
                }
            }
        }

        return $model;
    }
}
