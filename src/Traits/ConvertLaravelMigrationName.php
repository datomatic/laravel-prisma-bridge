<?php

namespace Datomatic\LaravelPrismaBridge\Traits;

trait ConvertLaravelMigrationName
{
    /**
     * Convert laravel migration name to prisma format
     */
    protected function convertLaravelMigrationName(string $name, string $prefix = ''): array
    {
        $timestamp = preg_match('/^\d{4}_\d{2}_\d{2}_\d{6}_/', $name, $timestampMatches);
        if ($timestamp) {
            $timestamp = preg_replace('/^(\d{4})_(\d{2})_(\d{2})_(\d{6})_/', '$1$2$3$4', $timestampMatches[0]);
        }
        $name = preg_replace('/^(\d{4})_(\d{2})_(\d{2})_(\d{6})_/', '', $name);
        $name = (! empty($prefix) ? $prefix.'_' : '').$name;

        return [$name, $timestamp];
    }
}
