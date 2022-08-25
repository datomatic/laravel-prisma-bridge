<?php

namespace Datomatic\LaravelPrismaBridge\Migrations;

use Datomatic\LaravelPrismaBridge\Traits\ConvertLaravelMigrationName;
use Illuminate\Database\ConnectionResolverInterface as Resolver;
use Illuminate\Database\Migrations\DatabaseMigrationRepository as BaseDatabaseMigrationRepository;
use Illuminate\Support\Str;

class DatabaseMigrationRepository extends BaseDatabaseMigrationRepository
{
    use ConvertLaravelMigrationName;

    /**
     * The sql file repository instance.
     *
     * @var SqlFileRepository
     */
    protected $sqlFileRepository;

    /**
     * {@inheritDoc}
     */
    public function __construct(SqlFileRepository $sqlFileRepository, Resolver $resolver, $table)
    {
        $this->sqlFileRepository = $sqlFileRepository;
        parent::__construct($resolver, $table);
    }

    /**
     * {@inheritDoc}
     */
    public function createRepository()
    {
        $schema = $this->getConnection()->getSchemaBuilder();

        $queries = $schema->getConnection()->pretend(function () use ($schema) {
            $schema->create($this->table, function ($table) {
                // The migrations table is responsible for keeping track of which of the
                // migrations have actually run for the application. We'll create the
                // table to hold the migration file's path as well as the batch ID.
                $table->increments('id');
                $table->string('migration');
                $table->integer('batch');
            });
        });

        $queries = array_map(fn ($query) => $query['query'], $queries);

        $migrationName = $this->sqlFileRepository->createMigrationSqlFile('create_laravel_migrations_table', $queries);

        if (! $migrationName) {
            throw new \Exception('Error creating migration file for creating migrations table.');
        }

        parent::createRepository();

        passthru('export FORCE_COLOR=true && npx prisma migrate resolve --applied '.$migrationName);
    }

    /**
     * {@inheritDoc}
     */
    public function log($file, $batch)
    {
        [$name] = $this->convertLaravelMigrationName($file, 'log');

        $record = ['migration' => $file, 'batch' => $batch];
        $sql = $this->table()->getGrammar()->compileInsert($this->table(), $record);
        $sql = Str::replaceArray('?', array_map(fn ($v) => is_string($v) ? '"'.$v.'"' : $v, $record), $sql);
        $this->sqlFileRepository->createMigrationSqlFile($name, [$sql]);
    }

    /**
     * {@inheritDoc}
     */
    public function delete($migration)
    {
        [$name] = $this->convertLaravelMigrationName($migration->migration, 'delete');

        $sql = $this->table()->getGrammar()->compileDelete($this->table()->where('migration', $migration->migration));
        $sql = Str::replaceArray('?', array_map(fn ($v) => is_string($v) ? '"'.$v.'"' : $v, [$migration->migration]), $sql);

        $this->sqlFileRepository->createMigrationSqlFile($name, [$sql]);
    }
}
