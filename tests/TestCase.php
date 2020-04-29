<?php

namespace Mblarsen\LaravelRepository\Tests;

use PDO;
use Mblarsen\LaravelRepository\RepositoryServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app)
    {
        return [RepositoryServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app)
    {
        if (env('CI')) {
            $this->useMySQL($app);
        } else {
            $this->useSqlite($app);
        }
    }

    protected function useSqlite($app)
    {
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
    }

    protected function useMySQL($app)
    {
        $database = env('SCRUTINIZER')
            ? 'scrutinizer'
            : 'laravel_repository';
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver' => 'mysql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', $database),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql')
                ? array_filter([
                    PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
                ])
                : [],

        ]);
    }

    public function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:wipe', ['--database' => 'testbench'])->run();
        $this->artisan('migrate', ['--database' => 'testbench'])->run();
    }
}
