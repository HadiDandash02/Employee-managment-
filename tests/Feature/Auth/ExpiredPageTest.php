<?php

namespace Tests\Feature\Auth;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 419 "Page Expired" (a form sent with an old CSRF token) is never shown as a bare error page.
 */
class ExpiredPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Laravel skips the CSRF check while testing; switch the real check back on.
        $this->app->bind(ValidateCsrfToken::class, fn ($app) => new class($app, $app['encrypter']) extends ValidateCsrfToken
        {
            protected function runningUnitTests()
            {
                return false;
            }
        });
    }

    public function test_logout_with_an_old_token_still_logs_out(): void
    {
        $this->actingAs(User::factory()->hr()->create());

        // e.g. a second tab, or the Back button after logging out
        $this->post(route('logout'), ['_token' => 'old-token'])
            ->assertRedirect(route('login'))
            ->assertSessionHas('success', 'You have been logged out.');

        $this->assertGuest();
        $this->get(route('login'))->assertOk()->assertSee('You have been logged out.');
    }

    public function test_another_form_with_an_old_token_goes_back_with_a_message_and_is_not_saved(): void
    {
        $employee = Employee::factory()->create(['first_name' => 'Old']);
        $this->actingAs(User::factory()->hr()->create());

        $this->from(route('employees.edit', $employee))
            ->put(route('employees.update', $employee), ['_token' => 'old-token', 'first_name' => 'New'])
            ->assertRedirect(route('employees.edit', $employee))
            ->assertSessionHas('warning', 'Your session expired, so the form was not sent. Please try again.')
            ->assertSessionHasInput('first_name', 'New');

        // The protection still works: the old token did not save anything.
        $this->assertSame('Old', $employee->fresh()->first_name);
        $this->assertAuthenticated();
    }

    public function test_an_expired_login_form_keeps_the_username_but_not_the_password(): void
    {
        $this->from(route('login'))
            ->post(route('login.store'), ['_token' => 'old-token', 'login' => 'helen', 'password' => 'secret'])
            ->assertRedirect(route('login'))
            ->assertSessionHas('warning')
            ->assertSessionHasInput('login', 'helen')
            ->assertSessionMissing('_old_input.password');
    }

    public function test_ajax_requests_keep_the_json_419(): void
    {
        $this->actingAs(User::factory()->hr()->create());

        $this->deleteJson(route('employees.destroy', Employee::factory()->create()), ['_token' => 'old-token'])
            ->assertStatus(419);
    }

    public function test_the_right_token_still_works(): void
    {
        $this->actingAs(User::factory()->hr()->create());
        $this->get(route('employees.index')); // starts the session and its token

        $this->post(route('logout'), ['_token' => csrf_token()])->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
