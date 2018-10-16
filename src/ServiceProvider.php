<?php

namespace Lxj\Laravel\Tars;

use Laravelista\LumenVendorPublish\VendorPublishCommand;
use Lxj\Laravel\Tars\Commands\Deploy;
use Lxj\Laravel\Tars\Commands\Tars;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerCommands();
    }

    /**
     * Register commands.
     */
    protected function registerCommands()
    {
        $this->commands([
            VendorPublishCommand::class,
            Deploy::class,
            Tars::class,
        ]);
    }

    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        app()->configure('tars');

        $this->publishes([
            __DIR__ . '/index.php' => base_path('index.php'),
            __DIR__ . '/services.php' => base_path('services.php'),
            __DIR__ . '/config/tars.php' => base_path('config/tars.php'),
        ]);
    }
}
