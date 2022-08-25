<?php

namespace Datomatic\LaravelPrismaBridge\Providers;

use Datomatic\LaravelPrismaBridge\Migrations\DatabaseMigrationRepository;
use Datomatic\LaravelPrismaBridge\Migrations\Migrator;
use Datomatic\LaravelPrismaBridge\Migrations\SqlFileRepository;
use Illuminate\Database\MigrationServiceProvider;

class PrismaServiceProvider extends MigrationServiceProvider
{
    /**
     * {@inheritDoc}
     */
    protected function registerRepository()
    {
        $this->app->singleton('sqlfile.repository', function () {
            return new SqlFileRepository();
        });

        $this->app->singleton('migration.repository', function ($app) {
            $table = $app['config']['database.migrations'];

            return new DatabaseMigrationRepository($app['sqlfile.repository'], $app['db'], $table);
        });
    }

    /**
     * {@inheritDoc}
     */
    protected function registerMigrator()
    {
        $this->app->singleton('migrator', function ($app) {
            return new Migrator($app['sqlfile.repository'], $app['migration.repository'], $app['db'], $app['files'], $app['events']);
        });
    }
}
