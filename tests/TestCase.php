<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Decouple backend tests from the built front-end: stub the @vite
        // directive so views render without a Vite manifest. The asset build is
        // verified separately by the CI "assets" job.
        $this->withoutVite();
    }
}
