<?php

namespace Ow\Manageable\Events;

use Ow\Manageable\Contracts\Manageable;
use Illuminate\Queue\SerializesModels;

class FileAttachedTo
{
    use SerializesModels;

    public $entity;

    public $file;

    /**
     * Create a new event instance.
     *
     * @param  Manageable  $entity
     * @param  mixed $file
     * @return void
     */
    public function __construct(Manageable $entity, $file)
    {
        $this->entity = $entity;
        $this->file = $file;
    }
}
