<?php

namespace Ow\Manageable\Contracts;

use Illuminate\Contracts\Auth\Access\Authorizable;

interface PolicyContract
{
    public function access(Authorizable $user, Manageable $entity);
}
