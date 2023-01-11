<?php

namespace Anwardote\ExportImportWpLaravel;

use Anwardote\ExportImportWpLaravel\Commands\ImportCoureCommand;
use Anwardote\ExportImportWpLaravel\Commands\ImportWpCouponCommand;
use Anwardote\ExportImportWpLaravel\Commands\ImportWpReadyCommand;
use Anwardote\ExportImportWpLaravel\Commands\ImportWpRegisterCommand;
use Anwardote\ExportImportWpLaravel\Commands\ImportWpUserCommand;
use Anwardote\ExportImportWpLaravel\Commands\ImportWpDriverEdCommand;
use Anwardote\ExportImportWpLaravel\Commands\ImportWpLessonsCommand;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;

class ExportImportWpLaravelServiceProvider extends ServiceProvider
{

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
//        $this->app->register(CrewlixTenantEventServiceProvider::class);
//        $this->app->register(CrewlixTenantScheduleServiceProvider::class);

        // mergeConfigFrom
        $this->axilweb_mergeConfigFrom();
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        Schema::defaultStringLength(255);

        $this->axilweb_registerCommands();

        // load routes
        $this->axilweb_load_routes();

        // load migrations
        $this->axilweb_load_migrations();
    }


    // custom methods
    protected function axilweb_mergeConfigFrom()
    {
        $this->mergeConfigFrom(__DIR__.'/config/config.php', 'export_import_laravel');
    }

    // load routes
    protected function axilweb_load_routes()
    {
        $this->loadRoutesFrom(__DIR__.'/routes/index.php');
    }


    // load migrations
    protected function axilweb_load_migrations()
    {
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
    }

    protected function axilweb_registerCommands()
    {
        $this->commands([
            ImportCoureCommand::class,
            ImportWpReadyCommand::class,
            ImportWpUserCommand::class,
            ImportWpRegisterCommand::class,
            ImportWpDriverEdCommand::class,
            ImportWpLessonsCommand::class,
            ImportWpCouponCommand::class
        ]);
    }

}
