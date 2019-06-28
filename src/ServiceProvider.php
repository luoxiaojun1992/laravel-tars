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
        $commands = [
            Deploy::class,
            Tars::class,
        ];
        if (Util::isLumen()) {
            array_push($commands, VendorPublishCommand::class);
        }
        $this->commands($commands);
    }

    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        if (Util::isLumen()) {
            app()->configure('tars');
        }

        $tarsServantDir = base_path('app/Tars/servant');
        $tarsServantImplDir = base_path('app/Tars/impl');
        $tarsCservantDir = base_path('app/Tars/cservant');

        if (!is_dir($tarsServantDir)) {
            mkdir($tarsServantDir, 0755, true);
        }
        if (!is_dir($tarsServantImplDir)) {
            mkdir($tarsServantImplDir, 0755, true);
        }
        if (!is_dir($tarsCservantDir)) {
            mkdir($tarsCservantDir, 0755, true);
        }

        $this->publishes([
            __DIR__ . '/index.php' => base_path('index.php'),
            __DIR__ . '/Tars/cservant/.gitkeep' => $tarsCservantDir . '/.gitkeep',
            __DIR__ . '/services.php' => base_path('services.php'),
            __DIR__ . '/../tars/tars.proto.php' => base_path('../tars/tars.proto.php'),
            __DIR__ . '/config/tars.php' => base_path('config/tars.php'),
            __DIR__ . '/../scripts/tars2php.sh' => base_path('../scripts/tars2php.sh'),
            __DIR__ . '/Tars/servant/.gitkeep' => $tarsServantDir . '/.gitkeep',
            __DIR__ . '/Tars/impl/.gitkeep' => $tarsServantImplDir . '/.gitkeep',
        ], 'tars');
    }
}
