<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Fortify\Features;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Feature tests must not depend on an external Inertia SSR process.
        // Tests that specifically cover SSR can explicitly enable it.
        config()->set(
            'inertia.ssr.enabled',
            false,
        );
    }

    protected function skipUnlessFortifyHas(
        string $feature,
        ?string $message = null,
    ): void {
        if (! Features::enabled($feature)) {
            $this->markTestSkipped(
                $message
                    ?? "Fortify feature [{$feature}] is not enabled.",
            );
        }
    }
}
