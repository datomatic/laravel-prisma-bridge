<?php

namespace Datomatic\LaravelPrismaBridge;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelPrismaBridgeServiceProvider extends PackageServiceProvider
{
    // More info: https://github.com/spatie/laravel-package-tools
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-prisma-bridge')
            ->hasConfigFile();
    }
}
