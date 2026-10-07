<?php

namespace App\Http\Middleware;

use App\Services\Backup;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

use function Illuminate\Support\defer;

/**
 * Makes one backup a day, the first time the app is used that day, after the
 * response is sent. It works however the app was started, and a failed backup
 * never breaks the request: it's logged, shown on the admin's Home page, and
 * retried an hour later.
 */
class DailyBackup
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $key = 'backup:daily:'.today()->toDateString();
        if (config('backup.daily') && Cache::add($key, true, now()->addDays(2))) {
            defer(function () use ($key) {
                try {
                    Backup::create();
                } catch (Throwable $e) {
                    report($e);
                    Cache::put($key, true, now()->addHour());
                }
            });
        }

        return $response;
    }
}
