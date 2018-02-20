<?php

namespace Ow\Manageable\Policies;

use Ow\Manageable\Contracts\Manageable;

class PolicyFactory
{
    // @todo get this from a config? or the entity
    protected static $repos = [
        // Custom repos for models
        // $model => $reponame
    ];

    public static function build(Manageable $entity)
    {
        try {
            // 1. if set on the array uses the appropriate class
            if (array_search(get_class($entity), static::$repos)) {
                $repo_class = static::$repos[get_class($entity)];

                return new $repo_class($entity);
            }

            // 2. tries to check if there is a ModelNameRepository on the folder
            $model_classname = explode('\\', $modelname);

            $classname = array_last($model_classname);
            array_pop($model_classname);

            $repo_class = implode('\\', $model_classname) . '\\' . $classname . 'Repository';

            return new $repo_class($entity);
        } catch (\Exception $e) {
            // do nothing for now, the class was not found, that is ok
        }

        // 3. defaults to the basic implementation of the Manageable Repository
        return new Policy($entity);
    }
}
