<?php

namespace Ow\Manageable\Contracts;

use Ow\Manageable\Contracts\Manageable;

interface CriteriaContract
{
    public function apply($entity, RepositoryContract $repository = null);
}
