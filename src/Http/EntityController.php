<?php

namespace Ow\Manageable\Http;

use Ow\Manageable\Http\Traits\Crudful;
use Ow\Manageable\Http\Traits\ResolveEntityRequest;

class EntityController extends Controller
{
    use Crudful, // index, show, store, update, destroy
        ResolveEntityRequest; // resolveRequest
}
