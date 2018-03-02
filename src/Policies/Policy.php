<?php

namespace Ow\Manageable\Policies;

use Ow\Manageable\Contracts\PolicyContract;
use Ow\Manageable\Contracts\Manageable;
use Illuminate\Contracts\Auth\Access\Authorizable;

use Illuminate\Http\Request;

class Policy implements PolicyContract
{
    protected $entity;

    protected $entity_class;

    public function __construct(Manageable $entity)
    {
        $this->entity = $entity;
        $this->entity_class = get_class($entity);
    }

    public function access(Authorizable $user, Manageable $entity)
    {
        // Has gate 'access-entity' defined? yes >> apply it //
        return true;
    }

    public function index(Authorizable $user, $params = [])
    {
        return true;
    }

    public function store(Authorizable $user, $params = [])
    {
        return true;
    }

    public function update(Authorizable $user, $params = [])
    {
        return true;
    }

    public function destroy(Authorizable $user, $params = [])
    {
        return true;
    }

    public function restore(Authorizable $user, $params = [])
    {
        return true;
    }
}
