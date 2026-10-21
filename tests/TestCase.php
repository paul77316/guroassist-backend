<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /** Runs when the app is built, before any trait (such as RefreshDatabase) can wipe a database. */
    public function createApplication()
    {
        $app = parent::createApplication();

        $default = $app['config']->get('database.default');
        $name = (string) $app['config']->get("database.connections.{$default}.database");

        if (! str_ends_with($name, '_test')) {
            throw new RuntimeException("Refusing to run tests: database [{$name}] does not end in _test.");
        }

        return $app;
    }
}