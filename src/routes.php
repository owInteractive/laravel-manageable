<?php

Route::group(['as' => 'manageable.', 'namespace' => '\\Ow\\Manageable\\Http',], function () {
    Route::get('/list/{entity}', 'Controller@index')->where('entity', '(.*)')->name('manageable.index');
    Route::get('/show/{id}/{entity}', 'Controller@show')->where('entity', '(.*)')->name('manageable.show');
});
