<?php

namespace Ow\Manageable\Http\Traits;

use Ow\Manageable\Entities\EntityFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use ReflectionClass;

trait ResolveEntityRequest
{
    protected function resolveEntityRequest($entity_class)
    {
        // If there is a declared request it will use it
        try {
            $requests_namespace = config('manageable.requests_namespace', 'App\\Http\\Requests');
            $entities_namespace = config('manageable.entities_namespace', 'App\\Entities');

            $request_class = str_replace($entities_namespace, $requests_namespace, $entity_class);

            return resolve($request_class . 'Request');
        } catch (\Exception $e) {
            if ($e instanceof \Illuminate\Validation\ValidationException) {
                throw $e;
            }
        }

        if (!isset($request)) {
            try {
                $reflection = new ReflectionClass(Request::class);

                $reflection->getProperty('reflection_model')->setValue($entity_class);

                $request = resolve(Request::class);

                $reflection->getProperty('reflection_model')->setValue(null);

                return $request;
            } catch (Exception $e) {
                if ($e instanceof \Illuminate\Validation\ValidationException) {
                    throw $e;
                }
            }
        }

        return resolve(Request::class);
    }
}
