<?php

namespace Mawdoo3\Waraqa;

use Illuminate\Support\ServiceProvider;
use Mawdoo3\Waraqa\Console\WaraqaIntegrationCommand;

class WaraqaIntegrationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadRoutesFrom(__DIR__."/routes/web.php");
        // $this->loadViewsFrom(__DIR__."/views/");
        // $this->loadMigrationsFrom(__DIR__."/database/migrations");
        // $this->mergeConfigFrom(__DIR__."/config/sp_mawdoo3_laravel.php",'search');
        $this->publishes([__DIR__.'/config/awsIntegration.php'  => config_path('awsIntegration.php')],'config');
        $this->publishes([__DIR__.'/config/mediaWikiIntegration.php'  => config_path('mediaWikiIntegration.php')],'config');
        $this->publishes([__DIR__.'/config/waragaIntegration.php'  => config_path('waragaIntegration.php')],'config');

        if ($this->app->runningInConsole()) {
            $this->commands([
                WaraqaIntegrationCommand::class,
            ]);
        }
    }
}





