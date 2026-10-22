<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * Runs after the application boots and before traits such as RefreshDatabase wipe the schema.
     *
     * @return array<class-string, class-string>
     */
    protected function setUpTraits()
    {
        $this->ensureTestingDatabase();

        return parent::setUpTraits();
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Feature tests assert on Inertia props, not on compiled assets: no `npm run build` needed to run them.
        $this->withoutVite();
    }

    private function ensureTestingDatabase(): void
    {
        $connection = config()->string('database.default');
        $database = config()->string("database.connections.{$connection}.database");

        if ($connection !== 'mysql' || ! str_contains($database, '_testing')) {
            throw new RuntimeException(
                "Tests must run against a MySQL database whose name contains \"_testing\" (got {$connection}:{$database}). ".
                'They wipe the schema, so they refuse to touch any other database.'
            );
        }
    }
}
