<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/**
 * Every logged-in page lives under the user's role: /admin/..., /hr/..., /manager/..., /employee/...
 * (config/permissions.php "prefix", the {panel} part of the routes).
 *
 * - route('employees.index') etc. automatically use the logged-in user's own prefix.
 * - Opening another role's prefix (e.g. HR opening /admin/...) is a 404, so nobody sees another role's area.
 */
class SetPanelPrefix
{
    public function handle(Request $request, Closure $next): Response
    {
        $prefix = $request->user()?->url_prefix;

        if ($prefix) {
            URL::defaults(['panel' => $prefix]);
        }

        $route = $request->route();
        if ($route && $route->hasParameter('panel')) {
            abort_unless($prefix !== null && $route->parameter('panel') === $prefix, 404);

            // Controllers don't need it; remove it so it isn't passed to their methods.
            $route->forgetParameter('panel');
        }

        return $next($request);
    }
}
