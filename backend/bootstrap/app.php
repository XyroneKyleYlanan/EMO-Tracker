<?php

use App\Http\Middleware\CompletePastEvents;
use App\Http\Middleware\DailyBackup;
use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\ForceJsonResponse;
use App\Models\Document;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Browsers reach Laravel through the frontend server on this computer,
        // which passes on each device's own address (used by the login limit).
        $middleware->trustProxies(at: ['127.0.0.1', '::1'], headers: Request::HEADER_X_FORWARDED_FOR);

        $middleware->api(prepend: [
            ForceJsonResponse::class,
            CompletePastEvents::class,
        ]);

        $middleware->api(append: [
            DailyBackup::class,
        ]);

        $middleware->alias([
            'role' => EnsureUserHasRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(function ($request) {
            return $request->is('api/*') || $request->expectsJson();
        });

        $exceptions->render(function (AuthenticationException $e, $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }
        });

        // Usually something deleted in another window while this one had it open.
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*') && $e->getPrevious() instanceof ModelNotFoundException) {
                return response()->json(['message' => 'This item no longer exists. It may have been deleted. Refresh the page.'], 404);
            }
        });

        // A file over PHP's size limit is dropped before the app ever sees it.
        $exceptions->render(function (PostTooLargeException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => Document::tooLargeMessage()], 413);
            }
        });
    })->create();
