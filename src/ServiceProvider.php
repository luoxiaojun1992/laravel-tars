<?php

namespace Lxj\Laravel\Tars;

use Lxj\Laravel\Tars\Commands\Deploy;
use Lxj\Laravel\Tars\Commands\Tars;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot(){
        $this->publishes([
            __DIR__ . '/index.php' => base_path('index.php'),
            __DIR__ . '/services.php' => base_path('services.php'),
        ]);
    }

    /**
     * Register commands.
     */
    protected function registerCommands()
    {
        $this->commands([
            Deploy::class,
            Tars::class,
        ]);
    }
}
