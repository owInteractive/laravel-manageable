<?php

namespace Ow\Manageable;

use Illuminate\Support\Facades\Facade as IlluminateFacade;
use Ow\Manageable\Http\Controller;

use Illuminate\Support\Facades\Route;

class Facade extends IlluminateFacade
{
    protected static function getFacadeAccessor()
    {
        return 'manageable';
    }

   /**
     * Register the typical authentication routes for an application.
     *
     * @return void
     */
    public static function routes()
    {
        // Authentication Routes...
        Route::group(['as' => 'manageable.'], function () {
            Route::get('/{entity}', '\\' . Controller::class . '@index')
                ->where('entity', '[a-zA-Z\/\-]*')
                ->name('index');

            Route::post('/{entity}', '\\' . Controller::class . '@store')
                ->where('entity', '[a-zA-Z\/\-]*')
                ->name('store');

            Route::get('/{entity}/{id}', '\\' . Controller::class . '@show')
                ->where('id', '[0-9]+')
                ->where('entity', '[a-zA-Z\/\-]*')
                ->name('show');

            Route::put('/{entity}/{id}', '\\' . Controller::class . '@update')
                ->where('id', '[0-9]+')
                ->where('entity', '[a-zA-Z\/\-]*')
                ->name('update');

            Route::delete('/{entity}/{id}', '\\' . Controller::class . '@destroy')
                ->where('id', '[0-9]+')
                ->where('entity', '[a-zA-Z\/\-]*')
                ->name('destroy');

            // Uploads media
            Route::post('/{entity}/{id}/media', '\\' . Controller::class . '@uploadMedia')
                ->where('id', '[0-9]+')
                ->where('entity', '[a-zA-Z\/\-]*')
                ->name('media.store');
        });
    }
}
