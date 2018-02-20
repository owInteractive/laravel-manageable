<?php

namespace Ow\Manageable\Entities\Traits;

trait Searchable
{
    public function getSearchableFields()
    {
        return $this->search_fields ?? ($this->fillable ?? []);
    }
}
