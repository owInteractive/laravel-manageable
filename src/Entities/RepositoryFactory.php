<?php

namespace Ow\Manageable\Entities;

use Ow\Manageable\Contracts\Manageable;

class RepositoryFactory
{
    // @todo get this from a config? or the entity ??
    protected static $repos = [
        // Custom repos for models
        // $model => $reponame
    ];

    public static function build(Manageable $entity)
    {
        $entity_class = get_class($entity);

        try {
            // If set on the array uses the appropriate class
            if (array_search($entity_class, static::$repos)) {
                $repo_class = static::$repos[$entity_class];

                return new $repo_class($entity);
            }

            // Tries to check if there is a ModelNameRepository on the folder
            $model_classname = explode('\\', $entity_class);

            $classname = array_last($model_classname);
            array_pop($model_classname);

            $repo_class = implode('\\', $model_classname) . '\\' . $classname . 'Repository';

            if (class_exists($repo_class)) {
                $repository = new $repo_class($entity);
            } else {
                $repository = new Repository($entity);
            }
        } catch (\Exception $e) {
            // Defaults to the basic implementation of the Manageable Repository
            $repository = new Repository($entity);
        }

        // 2. Processes the Default Criterias
        $criterias = config('manageable.custom_criterias', []);
        if (isset($criterias[$entity_class])) {
            foreach ($criterias[$entity_class] as $criteria) {
                $repository->pushCriteria(resolve($criteria));
            }
        }

        return $repository;
    }
}
