<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Vite;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock Vite manifest for tests
        $this->withoutVite();

        // Disable Inertia page existence check for tests
        config(['inertia.testing.ensure_pages_exist' => false]);
    }
}
