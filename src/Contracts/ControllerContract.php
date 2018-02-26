<?php

namespace Ow\Manageable\Contracts;

interface ControllerContract
{
    // Crudful
    public function index();
    public function show();
    public function store();
    public function update();
    public function destroy();

    // Aggredated
}
