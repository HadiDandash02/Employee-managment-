<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/**
 * The management area lives at /{panel}: /admin for admins, /hr for HR.
 *
 * - route('admin.dashboard') etc. automatically use the logged-in user's own prefix.
 * - Opening another role's prefix (e.g. HR opening /admin) is a 404, so HR never sees Admin pages.
 */
class SetPanelPrefix
{
    public function handle(Request $request, Closure $next): Response
    {
        $slug = $request->user()?->panel_slug;

        if ($slug) {
            URL::defaults(['panel' => $slug]);
        }

        $route = $request->route();
        if ($route && $route->hasParameter('panel')) {
            abort_unless($slug !== null && $route->parameter('panel') === $slug, 404);

            // Controllers don't need it; remove it so it isn't passed to their methods.
            $route->forgetParameter('panel');
        }

        return $next($request);
    }
}
