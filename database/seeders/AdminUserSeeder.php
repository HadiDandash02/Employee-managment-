<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Create the "admin" user with every permission.
     *
     * The password comes from ADMIN_PASSWORD in .env (default: Admin@12345).
     * Running this again does not change an existing admin's password.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Administrator',
                'email' => env('ADMIN_EMAIL', 'admin@example.com'),
                'password' => env('ADMIN_PASSWORD', 'Admin@12345'),
                'role' => 'admin',
                'email_verified_at' => now(),
            ],
        );
    }
}
