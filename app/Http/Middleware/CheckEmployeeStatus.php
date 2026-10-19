<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Only active (not suspended) users may continue.
 *
 * Registered as the "active" alias in bootstrap/app.php and put on the employee pages,
 * so the rule lives in ONE place instead of an if-check repeated in every controller.
 * It runs after "auth", so a user is always logged in here.
 */
class CheckEmployeeStatus
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->is_active) {
            // 403 Forbidden: we know who you are, but you may not open this page.
            abort(403, 'Your account is suspended. Please contact HR.');
        }

        return $next($request);
    }
}
