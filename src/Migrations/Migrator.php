<?php

namespace Datomatic\LaravelPrismaBridge\Migrations;

use Datomatic\LaravelPrismaBridge\Traits\ConvertLaravelMigrationName;
use Doctrine\DBAL\Schema\SchemaException;
use Illuminate\Console\View\Components\Error;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\ConnectionResolverInterface as Resolver;
use Illuminate\Database\Events\MigrationEnded;
use Illuminate\Database\Events\MigrationStarted;
use Illuminate\Database\Migrations\MigrationRepositoryInterface;
use Illuminate\Database\Migrations\Migrator as BaseMigrator;
use Illuminate\Filesystem\Filesystem;
use ReflectionClass;

class Migrator extends BaseMigrator
{
    use ConvertLaravelMigrationName;

    /**
     * The sql file repository instance.
     */
    protected SqlFileRepository $sqlFileRepository;

    /**
     * {@inheritDoc}
     */
    public function __construct(
        SqlFileRepository $sqlFileRepository,
        MigrationRepositoryInterface $repository,
        Resolver $resolver,
        Filesystem $files,
        Dispatcher $dispatcher = null)
    {
        $this->sqlFileRepository = $sqlFileRepository;
        parent::__construct($repository, $resolver, $files, $dispatcher);
    }

    /**
     * {@inheritDoc}
     */
    protected function runMigration($migration, $method)
    {
        if (method_exists($migration, $method)) {
            $this->fireMigrationEvent(new MigrationStarted($migration, $method));

            $this->createSqlFileFromMigration($migration, $method);

            $this->fireMigrationEvent(new MigrationEnded($migration, $method));
        }
    }

    /**
     * Create a sql file starting from a migration file
     *
     * @param  object  $migration
     * @param  string  $method
     */
    protected function createSqlFileFromMigration($migration, $method): void
    {
        $reflectionClass = new ReflectionClass($migration);
        $name = $this->getMigrationName($reflectionClass->getFileName());
        [$name] = $this->convertLaravelMigrationName($name, $method);

        try {
            $queries = $this->getQueries($migration, $method);
        } catch (SchemaException $e) {
            $this->write(Error::class, sprintf(
                '[%s] failed to dump queries. This may be due to changing database columns using Doctrine, which is not supported while pretending to run migrations.',
                $name,
            ));

            return;
        }

        $queries = array_map(fn ($query) => $query['query'], $queries);

        $this->sqlFileRepository->createMigrationSqlFile($name, $queries);
    }

    /**
     * Run prisma commands to run migrations, update schema and generate models
     */
    protected function runPrismaMigrations(): void
    {
        passthru('export FORCE_COLOR=true && npx prisma migrate deploy');
        passthru('export FORCE_COLOR=true && npx prisma db pull');
        passthru('export FORCE_COLOR=true && npx prisma generate');
    }

    /**
     * {@inheritDoc}
     */
    public function runPending(array $migrations, array $options = [])
    {
        parent::runPending($migrations, $options);
        $this->runPrismaMigrations();
    }

    /**
     * {@inheritDoc}
     */
    public function rollback($paths = [], array $options = [])
    {
        $result = parent::rollback($paths, $options);
        $this->runPrismaMigrations();

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function reset($paths = [], $pretend = false)
    {
        $result = parent::reset($paths, $pretend);
        $this->runPrismaMigrations();

        return $result;
    }
}
