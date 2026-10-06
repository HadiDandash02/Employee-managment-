<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

/**
 * "My account": every logged-in user, whatever their role, can change their own password here.
 */
class AccountController extends Controller
{
    public function edit(Request $request)
    {
        return view('account.edit', ['user' => $request->user()]);
    }

    public function updatePassword(Request $request)
    {
        $data = $request->validateWithBag('password', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', 'different:current_password', Password::min(8)],
        ], [
            'current_password.current_password' => 'Your current password is incorrect.',
            'password.different' => 'The new password must be different from the current one.',
        ]);

        $request->user()->update(['password' => $data['password']]);

        // Sign out this account everywhere else (other browsers / "remember me" devices).
        Auth::logoutOtherDevices($data['password']);
        $request->session()->regenerate();

        return redirect()->route('account.edit')->with('success', 'Your password has been changed.');
    }
}
