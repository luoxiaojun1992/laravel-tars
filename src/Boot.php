<?php

namespace Lxj\Laravel\Tars;

use Illuminate\Support\Facades\Log;
use Monolog\Logger;

class Boot
{
    private static $booted = false;

    public static function handle()
    {
        if (!self::$booted) {
            $localConfig = config('tars');

            list($hostname, $port, $appName, $serverName) = Util::parseTarsConfig($localConfig['deploy_cfg']);

            if (!empty($localConfig['tarsregistry'])) {
                $logLevel = isset($localConfig['log_level']) ? $localConfig['log_level'] : Logger::INFO;
                $communicatorConfigLogLevel = isset($localConfig['communicator_config_log_level']) ? $localConfig['communicator_config_log_level'] : 'INFO';

                self::fetchConfig($localConfig['tarsregistry'], $appName, $serverName, $communicatorConfigLogLevel);

                self::setTarsLog($localConfig['tarsregistry'], $appName, $serverName, $logLevel, $communicatorConfigLogLevel);
            }

            self::$booted = true;
        }
    }

    private static function fetchConfig($tarsregistry, $appName, $serverName, $logLevel = 'INFO')
    {
        $configtext = Config::fetch($tarsregistry, $appName, $serverName, $logLevel);
        if ($configtext) {
            $remoteConfig = json_decode($configtext, true);
            foreach ($remoteConfig as $configName => $configValue) {
                app('config')->set($configName, array_merge(config($configName) ?: [], $configValue));
            }
        }
    }

    private static function setTarsLog($tarsregistry, $appName, $serverName, $level = Logger::INFO, $communicatorConfigLogLevel = 'INFO')
    {
        $config = Config::communicatorConfig($tarsregistry, $appName, $serverName, $communicatorConfigLogLevel);

        Log::driver()->pushHandler(new \Tars\log\handler\TarsHandler($config, 'tars.tarslog.LogObj', $level));
    }
}
