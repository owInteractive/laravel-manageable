<?php

namespace Ow\Manageable;

use Illuminate\Support\Facades\Facade as IlluminateFacade;
use Ow\Manageable\Http\EntityController;
use Ow\Manageable\Http\MediaController;
use Ow\Manageable\Http\FileController;

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
            // Media related
            Route::post('/media', '\\' . MediaController::class . '@store')
                ->name('media.store');

            Route::post('/{entity}/{id}/media', '\\' . MediaController::class . '@attach')
                ->where('id', '[0-9]+')
                ->where('entity', '[a-zA-Z\/\-]*')
                ->name('media.store');

            Route::delete('/{entity}/{id}/media/{media_id}', '\\' . MediaController::class . '@destroy')
                ->where('entity', '[a-zA-Z\/\-]*')
                ->where('id', '[0-9]+')
                ->where('media_id', '[0-9]+')
                ->name('media.destroy');

            // File related
            Route::post('/files', '\\' . FileController::class . '@store')
                ->name('files.store');

            Route::post('/{entity}/{id}/attach', '\\' . MediaController::class . '@attach')
                ->where('entity', '[a-zA-Z\/\-]*')
                ->where('id', '[0-9]+')
                ->name('media.store');

            // Route::delete('/{entity}/{id}/media/{media_id}', '\\' . MediaController::class . '@destroy')
            //     ->where('id', '[0-9]+')
            //     ->where('entity', '[a-zA-Z\/\-]*')
            //     ->where('media_id', '[0-9]+')
            //     ->name('media.destroy');

            // Management entities
            Route::get('/{entity}', '\\' . EntityController::class . '@index')
                ->where('entity', '[a-zA-Z\/\-]*')
                ->name('index');

            Route::post('/{entity}', '\\' . EntityController::class . '@store')
                ->where('entity', '[a-zA-Z\/\-]*')
                ->name('store');

            Route::get('/{entity}/{id}', '\\' . EntityController::class . '@show')
                ->where('id', '[0-9]+')
                ->where('entity', '[a-zA-Z\/\-]*')
                ->name('show');

            Route::put('/{entity}/{id}', '\\' . EntityController::class . '@update')
                ->where('id', '[0-9]+')
                ->where('entity', '[a-zA-Z\/\-]*')
                ->name('update');

            Route::delete('/{entity}/{id}', '\\' . EntityController::class . '@destroy')
                ->where('id', '[0-9]+')
                ->where('entity', '[a-zA-Z\/\-]*')
                ->name('destroy');
        });
    }
}
