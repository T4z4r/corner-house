<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Seed the session with a fresh password confirmation timestamp.
     */
    public function withConfirmedPassword(): static
    {
        return $this->withSession(['auth.password_confirmed_at' => now()->getTimestamp()]);
    }
}