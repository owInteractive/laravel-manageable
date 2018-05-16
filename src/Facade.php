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

            Route::post('/{entity}/{id}/files', '\\' . FileController::class . '@attach')
                ->where('entity', '[a-zA-Z\/\-]*')
                ->where('id', '[0-9]+')
                ->name('files.attach');

            Route::delete('/{entity}/{id}/files/{file_id}', '\\' . FileController::class . '@destroy')
                ->where('entity', '[a-zA-Z\/\-]*')
                ->where('id', '[0-9]+')
                ->where('file_id', '[0-9]+')
                ->name('files.destroy');

            Route::get('/{entity}/{id}/{file_id}/download', '\\' . FileController::class . '@download')
                ->where('entity', '[a-zA-Z\/\-]*')
                ->where('id', '[0-9]+')
                ->where('file_id', '[0-9]+')
                ->name('files.show');

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
