<?php

namespace Ow\Manageable\Events;

use Ow\Manageable\Contracts\Manageable;
use Illuminate\Queue\SerializesModels;

class MediaAttachedTo
{
    use SerializesModels;

    public $entity;

    public $media;

    /**
     * Create a new event instance.
     *
     * @param  Manageable  $entity
     * @param  mixed $media
     * @return void
     */
    public function __construct(Manageable $entity, $media)
    {
        $this->entity = $entity;
        $this->media = $media;
    }
}
