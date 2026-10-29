<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
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
