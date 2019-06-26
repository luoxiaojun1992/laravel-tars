<?php

namespace Lxj\Laravel\Tars;

use Illuminate\Support\Facades\Log;
use Monolog\Logger;
use Tars\App;

class Boot
{
    private static $booted = false;

    public static function handle()
    {
        if (!self::$booted) {
            $localConfig = config('tars');

            $logLevel = isset($localConfig['log_level']) ? $localConfig['log_level'] : Logger::INFO;

            $deployConfig = App::getTarsConfig();
            $tarsServerConf = $deployConfig['tars']['application']['server'];
            $appName = $tarsServerConf['app'];
            $serverName = $tarsServerConf['server'];

            self::fetchConfig($localConfig['deploy_cfg'], $appName, $serverName);

            self::setTarsLog($localConfig['deploy_cfg'], $logLevel);

            self::$booted = true;
        }
    }

    private static function fetchConfig($deployConfigPath, $appName, $serverName)
    {
        $configtext = Config::fetch($deployConfigPath, $appName, $serverName);
        if ($configtext) {
            $remoteConfig = json_decode($configtext, true);
            foreach ($remoteConfig as $configName => $configValue) {
                app('config')->set($configName, array_merge(config($configName) ?: [], $configValue));
            }
        }
    }

    private static function setTarsLog($deployConfigPath, $level = Logger::INFO)
    {
        $config = Config::communicatorConfig($deployConfigPath);

        Log::driver()->pushHandler(new \Tars\log\handler\TarsHandler($config, 'tars.tarslog.LogObj', $level));
    }
}
