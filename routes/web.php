<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Vue SPA catch-all — serves the built frontend for every non-API route
|--------------------------------------------------------------------------
*/
Route::get('/{any}', function () {
    return file_get_contents(public_path('app/index.html'));
})->where('any', '^(?!api).*$');