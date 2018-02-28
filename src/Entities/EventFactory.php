<?php

namespace Ow\Manageable\Entities;

use Ow\Manageable\Contracts\Manageable;

class EventFactory
{
    // @todo get this from a config? or the entity ??
    protected static $events = [
        // Custom events for models
        // $model => $reponame
    ];

    public static function build(Manageable $entity, $attributes = [], $context = 'saved')
    {
        $event = null;
        $entity_class = get_class($entity);

        try {
            // If set on the array uses the appropriate class
            if (array_search($entity_class, static::$events)) {
                $event_class = static::$events[$entity_class];

                return new $event_class($entity);
            }

            $events_namespace = config('manageable.event_namespace', '\\App\\Events');
            $entities_namespace = config('manageable.entities_namespace', 'App\\Entities');

            $event = str_replace($entities_namespace, $events_namespace, $entity_class);

            $event_class = $event . title_case($context);

            if (class_exists($event_class)) {
                $event = new $event_class($entity, $attributes);
            }
        } catch (\Exception $e) {
            // dd($e);
        }

        return $event;
    }
}
