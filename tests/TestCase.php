<?php

namespace Tests;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\URL;

abstract class TestCase extends BaseTestCase
{
    /**
     * Like in a real request (SetPanelPrefix), route('…') uses the acting user's own prefix:
     * /admin, /hr, /manager or /employee.
     */
    public function actingAs(Authenticatable $user, $guard = null)
    {
        if ($prefix = $user->url_prefix ?? null) {
            URL::defaults(['panel' => $prefix]);
        }

        return parent::actingAs($user, $guard);
    }
}
