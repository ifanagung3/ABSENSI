<?php

namespace Tests;

use Illuminate\Contracts\Auth\Authenticatable as UserContract;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function actingAs(UserContract $user, $guard = null): static
    {
        parent::actingAs($user, $guard);

        return $this->withSession([
            'auth_session_version' => (int) ($user->getAttribute('session_version') ?? 1),
        ]);
    }
}
