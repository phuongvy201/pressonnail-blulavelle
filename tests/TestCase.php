<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function beforeRefreshingDatabase()
    {
        $connection = (string) config('database.default');
        if ($connection !== 'sqlite') {
            throw new \RuntimeException(
                "Refusing to refresh the {$connection} database during tests. Tests must use sqlite (phpunit.xml)."
            );
        }
    }
}
