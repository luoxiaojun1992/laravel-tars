<?php

namespace Lxj\Laravel\Tars;

use Illuminate\Support\Facades\Log;
use Laravelista\LumenVendorPublish\VendorPublishCommand;
use Lxj\Laravel\Tars\Commands\Deploy;
use Lxj\Laravel\Tars\Commands\Tars;
use Monolog\Logger;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    protected $booted = false;

    protected $tarsBooted = false;

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
        if (!$this->booted) {
            app()->configure('tars');

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

            $publicResources = [
                __DIR__ . '/index.php' => base_path('index.php'),
                __DIR__ . '/Tars/cservant/.gitkeep' => $tarsCservantDir . '/.gitkeep',
                __DIR__ . '/services.php' => base_path('services.php'),
                __DIR__ . '/../tars/tars.proto.php' => base_path('../tars/tars.proto.php'),
            ];

            $this->publishes(array_merge($publicResources, [
                __DIR__ . '/config/tars.http.php' => base_path('config/tars.php'),
            ]), 'tars.http');

            $this->publishes(array_merge($publicResources, [
                __DIR__ . '/config/tars.tars.php' => base_path('config/tars.php'),
                __DIR__ . '/../scripts/tars2php.sh' => base_path('../scripts/tars2php.sh'),
                __DIR__ . '/Tars/servant/.gitkeep' => $tarsServantDir . '/.gitkeep',
                __DIR__ . '/Tars/impl/.gitkeep' => $tarsServantImplDir . '/.gitkeep',
            ]), 'tars.tars');

            $this->booted = true;
        } else {
            if (!$this->tarsBooted) {
                $localConfig = config('tars');

                list($hostname, $port, $appName, $serverName) = Util::parseTarsConfig($localConfig['deploy_cfg']);

                if (!empty($localConfig['tarsregistry'])) {
                    $logLevel = isset($localConfig['log_level']) ? $localConfig['log_level'] : Logger::INFO;
                    $communicatorConfigLogLevel = isset($localConfig['communicator_config_log_level']) ? $localConfig['communicator_config_log_level'] : 'INFO';

                    $this->fetchConfig($localConfig['tarsregistry'], $appName, $serverName, $communicatorConfigLogLevel);

                    $this->setTarsLog($localConfig['tarsregistry'], $appName, $serverName, $logLevel, $communicatorConfigLogLevel);
                }

                $this->tarsBooted = true;
            }
        }
    }

    private function fetchConfig($tarsregistry, $appName, $serverName, $logLevel = 'INFO')
    {
        $configtext = Config::fetch($tarsregistry, $appName, $serverName, $logLevel);
        if ($configtext) {
            $remoteConfig = json_decode($configtext, true);
            foreach ($remoteConfig as $configName => $configValue) {
                app('config')->set($configName, array_merge(config($configName) ?: [], $configValue));
            }
        }
    }

    private function setTarsLog($tarsregistry, $appName, $serverName, $level = Logger::INFO, $communicatorConfigLogLevel = 'INFO')
    {
        $config = Config::communicatorConfig($tarsregistry, $appName, $serverName, $communicatorConfigLogLevel);

        Log::driver()->pushHandler(new \Tars\log\handler\TarsHandler($config, 'tars.tarslog.LogObj', $level));
    }
}
