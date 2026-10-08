<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        // SÉCURITÉ : RefreshDatabase vide la base. On refuse de tourner ailleurs que sur SQLite,
        // pour ne jamais effacer la vraie base MySQL (lajanansanm).
        $driver = $_ENV['DB_CONNECTION'] ?? $_SERVER['DB_CONNECTION'] ?? getenv('DB_CONNECTION');

        if ($driver !== 'sqlite') {
            throw new \RuntimeException(
                'Tests refusés : DB_CONNECTION doit valoir "sqlite" dans phpunit.xml '
                . '(<env name="DB_CONNECTION" value="sqlite"/> et <env name="DB_DATABASE" value=":memory:"/>).'
            );
        }

        parent::setUp();

        $this->withoutVite(); // pas besoin de "npm run build" pour tester
    }
}
