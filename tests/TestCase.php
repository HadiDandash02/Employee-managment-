<?php

namespace Tests;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\URL;

abstract class TestCase extends BaseTestCase
{
    /**
     * Like in a real request (SetPanelPrefix), route('admin.…') uses the acting user's own
     * management prefix: /admin for admins, /hr for HR.
     */
    public function actingAs(Authenticatable $user, $guard = null)
    {
        if ($slug = $user->panel_slug ?? null) {
            URL::defaults(['panel' => $slug]);
        }

        return parent::actingAs($user, $guard);
    }
}
