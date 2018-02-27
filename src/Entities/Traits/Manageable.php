<?php

namespace Ow\Manageable\Entities\Traits;

trait Manageable
{
    public function getSearchableFields()
    {
        return $this->search_fields ?? ($this->fillable ?? []);
    }

    public function preProcess(array $data = [])
    {
        // no default pre processors
    }

    /**
     * POST PROCESSING
     *
     * @return array
     */
    protected function getPostProcessors()
    {
        return $this->post_processors ?? [];
    }

    //
    public function postProcess(array $data = [])
    {
        foreach ($this->getPostProcessors() as $processor) {
            resolve($processor)->process($this, $data);
        }

        return;
    }
}
