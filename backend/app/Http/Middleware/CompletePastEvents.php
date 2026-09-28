<?php

namespace App\Http\Middleware;

use App\Models\Event;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Marks events whose date has passed as completed before any API request is
 * handled, so the historical-record lock never depends on which page was
 * opened first. Runs before route model binding so bound events are fresh.
 */
class CompletePastEvents
{
    public function handle(Request $request, Closure $next): Response
    {
        Event::completePastEvents();

        return $next($request);
    }
}
