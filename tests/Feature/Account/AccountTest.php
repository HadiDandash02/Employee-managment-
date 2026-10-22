<?php

namespace Tests\Feature\Account;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AccountTest extends TestCase
{
    use RefreshDatabase;

    private function changePassword(User $user, array $data)
    {
        return $this->actingAs($user)
            ->from(route('account.edit'))
            ->put(route('account.password'), $data);
    }

    public function test_every_role_can_open_my_account(): void
    {
        foreach (array_keys(config('permissions.roles')) as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))
                ->get(route('account.edit'))
                ->assertOk()
                ->assertSee('Change password');
        }
    }

    public function test_user_can_change_own_password(): void
    {
        $user = User::factory()->create(['role' => 'viewer', 'password' => 'old-password']);

        $this->changePassword($user, [
            'current_password' => 'old-password',
            'password' => 'brand-new-pass',
            'password_confirmation' => 'brand-new-pass',
        ])->assertRedirect(route('account.edit'))->assertSessionHas('success');

        $this->assertTrue(Hash::check('brand-new-pass', $user->fresh()->password));
        $this->assertAuthenticatedAs($user);
    }

    public function test_wrong_current_password_is_rejected(): void
    {
        $user = User::factory()->create(['password' => 'old-password']);

        $this->changePassword($user, [
            'current_password' => 'not-it',
            'password' => 'brand-new-pass',
            'password_confirmation' => 'brand-new-pass',
        ])->assertSessionHasErrorsIn('password', ['current_password']);

        $this->assertTrue(Hash::check('old-password', $user->fresh()->password));
    }

    public function test_new_password_must_be_confirmed_long_enough_and_different(): void
    {
        $user = User::factory()->create(['password' => 'old-password']);

        $this->changePassword($user, [
            'current_password' => 'old-password',
            'password' => 'short',
            'password_confirmation' => 'other',
        ])->assertSessionHasErrorsIn('password', ['password']);

        $this->changePassword($user, [
            'current_password' => 'old-password',
            'password' => 'old-password',
            'password_confirmation' => 'old-password',
        ])->assertSessionHasErrorsIn('password', ['password']);
    }

    public function test_guests_cannot_open_my_account(): void
    {
        $this->get(route('account.edit', ['panel' => 'employee']))->assertRedirect(route('login'));
    }
}
