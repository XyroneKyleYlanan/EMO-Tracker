<?php

use Illuminate\Support\Facades\Route;

// The React app, built into public/app by `npm run build` in the frontend
// folder (office mode). Every page address (/login, /admin/schedule, ...)
// gets the same page and React shows the right screen; the API, the health
// check and the app's own files are left alone.
Route::get('/{page?}', function () {
    $app = public_path('app/index.html');

    if (! is_file($app)) {
        return response('EMO Tracker\'s screens are not built yet. In the frontend folder, run: npm run build', 503)
            ->header('Content-Type', 'text/plain');
    }

    // Always the newest page after an update; its scripts have new names each build.
    return response(file_get_contents($app))
        ->header('Content-Type', 'text/html; charset=UTF-8')
        ->header('Cache-Control', 'no-cache');
})->where('page', '^(?!api(/|$)|app/|storage/|up$).*');
