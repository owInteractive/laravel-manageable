<?php

namespace Ow\Manageable\Contracts;

interface Manageable
{
    // Quering the model
    public function getSearchableFields();

    // Pre and post operations
    public function preProcess(array $data = []);
    public function postProcess(array $data = []);
}
