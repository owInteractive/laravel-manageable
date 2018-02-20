<?php

Route::group(
    [
        'as' => 'api.manageable',
        'namespace' => '\\Ow\\Manageable\\Http',
        'middleware' => 'api',
        'prefix' => 'api'
    ],
    function () {
        Route::get('/list/{entity}', 'Controller@index')->where('entity', '(.*)')->name('manageable.index');
        Route::get('/show/{id}/{entity}', 'Controller@show')->where('entity', '(.*)')->name('manageable.show');
    }
);
