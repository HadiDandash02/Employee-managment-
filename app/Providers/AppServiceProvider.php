<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        // One password rule for the whole app (Password::defaults() in UserController and AccountController):
        // at least 8 characters, with upper- and lowercase letters, a number and a special character.
        Password::defaults(fn () => Password::min(8)->letters()->mixedCase()->numbers()->symbols());

        // Each permission in config/permissions.php becomes a Gate: @can('employees.create'), can:employees.create …
        foreach (array_keys(config('permissions.permissions')) as $permission) {
            Gate::define($permission, fn (User $user) => $user->hasPermission($permission));
        }

        // @can('manage-user', $other): HR may manage HR/Manager/Employee accounts, but never Admins.
        Gate::define('manage-user', fn (User $user, User $other) => $user->canManageUser($other));
    }
}
