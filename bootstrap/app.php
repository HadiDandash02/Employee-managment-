<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Guests are sent to the login page; logged-in users visiting /login go to their start page.
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn () => route('home'));

        // Route middleware alias: Route::middleware('active') = only active (not suspended) users.
        $middleware->alias([
            'active' => \App\Http\Middleware\CheckEmployeeStatus::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // 419 "Page Expired": the form carried an old CSRF token (another tab logged out or in, the Back button,
        // or the session ran out after SESSION_LIFETIME minutes). Instead of the bare error page:
        //   - Log out:    log out anyway (that was the goal) and show the login page;
        //   - other form: go back to the page with a message, keeping what was typed (except passwords).
        // The check itself stays on: the form is still NOT processed with an old token.
        $exceptions->render(function (HttpException $e, Request $request) {
            if ($e->getStatusCode() !== 419 || $request->expectsJson()) {
                return null; // everything else: Laravel's normal handling (AJAX keeps its JSON 419)
            }

            if ($request->routeIs('logout')) {
                Auth::guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')->with('success', 'You have been logged out.');
            }

            return redirect()->back()
                ->withInput($request->except(['password', 'password_confirmation', 'current_password', '_token']))
                ->with('warning', 'Your session expired, so the form was not sent. Please try again.');
        });

        // /api/* always answers errors as JSON (401, 403, 404, 422), even without an "Accept: application/json" header:
        // never a redirect to the login page or back to a form.
        $exceptions->shouldRenderJsonWhen(fn (Request $request) => $request->is('api/*') || $request->expectsJson());

        // The standard API validation error: {"message": "The given data was invalid.", "errors": {"field": ["…"]}}.
        // (Laravel 9+ would put the first error in "message"; the per-field messages in "errors" are unchanged.)
        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => 'The given data was invalid.', 'errors' => $e->errors()], $e->status);
            }
        });
    })->create();
