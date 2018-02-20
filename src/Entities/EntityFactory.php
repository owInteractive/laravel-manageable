<?php

namespace Ow\Manageable\Entities;

class EntityFactory
{
    public static function build(string $entity)
    {
        try {
            $class_name = static::name($entity);

            return new $class_name;
        } catch (\Error $e) {
            //
        }

        return null;
    }

    public static function name(string $entity)
    {
        if (config("manageable.custom_entities.{$entity}", null) !== null) {
            return config("manageable.custom_entities.{$entity}", null);
        } else {
            $parts = array_map(function ($part) {
                return ucfirst(camel_case($part));
            }, explode('/', $entity));

            $last_index = count($parts) - 1;
            $parts[$last_index] = str_singular($parts[$last_index]);

            return '\\' . config('manageable.entities_namespace') . '\\' . implode('\\', $parts);
        }
    }
}
