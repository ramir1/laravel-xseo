<?php

declare(strict_types=1);

namespace Ramir\Xseo\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use Ramir\Xseo\Facades\Xseo;
use Ramir\Xseo\XseoServiceProvider;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [XseoServiceProvider::class];
    }

    protected function getPackageAliases($app): array
    {
        return ['Xseo' => Xseo::class];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
    }
}
