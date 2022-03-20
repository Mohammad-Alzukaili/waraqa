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
        $this->publishes([__DIR__.'/config/waragaIntegration.php'  => $this->config_path('waragaIntegration.php')],'config');

        if ($this->app->runningInConsole()) {
            $this->commands([
                WaraqaIntegrationCommand::class,
                \Laravelista\LumenVendorPublish\VendorPublishCommand::class
            ]);
        }

        app()->configure('waragaIntegration');
    }


    function config_path($path = '')
    {
        return app()->basePath() . '/config' . ($path ? '/' . $path : $path);
    }
}





