<?php

namespace Ow\Manageable\Policies;

use Ow\Manageable\Entities\EntityFactory;
use Ow\Manageable\Contracts\Manageable;
use App\Entities\System\User;
use Route;

class PolicyFactory
{
    // @todo get this from a config? or the entity
    protected static $custom_policies = [
        // Custom repos for models
        // $model => $reponame
    ];

    public static function build(Manageable $entity)
    {
        $entity_class = get_class($entity);

        try {
            // 1. if set on the array uses the appropriate class
            if (array_search($entity_class, static::$custom_policies)) {
                $policy_class = static::$custom_policies[$entity_class];

                return new $policy_class($entity);
            }

            $policies_namespace = config('manageable.policies_namespace', '\\App\\Policies');
            $entities_namespace = config('manageable.entities_namespace', '\\App\\Entities');

            $policy_class = str_replace($entities_namespace, $policies_namespace, $entity_class) . 'Policy';

            if (class_exists($policy_class)) {
                return new $policy_class($entity);
            }
        } catch (\Exception $e) {
            // do nothing for now, the class was not found, that is ok
        }

        // 2. defaults to the basic implementation of the Manageable Repository
        $default_policy = config('manageable.base_policy', Policy::class);
        return new $default_policy($entity);
    }

    public function check(User $user, $params = [])
    {

        $request = resolve(\Illuminate\Http\Request::class);

        if ($request->route()->hasParameter('entity')) {
            $entity = EntityFactory::build($request->route()->parameter('entity'));

            if ($entity === null) {
                throw new \Illuminate\Database\Eloquent\ModelNotFoundException;
            }

            $policy = self::build($entity);

            list($controller, $action) = explode('@', Route::currentRouteAction());

            return $policy->{$action}($user, $params);
        }

        return false;
    }
}
