<?php

namespace Ow\Manageable\Entities\Traits;

use Ow\Manageable\Contracts\Manageable;

trait HasEntity
{
    protected function resetEntity()
    {
        $entity = new $this->entity_class;

        if (!$entity instanceof Manageable) {
            throw new \Exception('Class ' . $this->entity_class . 'must implement ' . Manageable::class . ' Interface.');
        }

        return $this->entity = $entity;
    }
}
