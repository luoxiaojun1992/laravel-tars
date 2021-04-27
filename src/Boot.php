<?php

namespace Lxj\Laravel\Tars;

use Monolog\Logger;
use Tars\App;

class Boot
{
    private static $booted = false;

    public static function handle($force = false)
    {
        if ((!self::$booted) || $force) {
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
        $communicatorConfig = Config::communicatorConfig($deployConfigPath);
        if (class_exists('Tars\log\handler\TarsHandler')) {
            $tarsLogHandlerClass = 'Tars\log\handler\TarsHandler';
        } else {
            $tarsLogHandlerClass = LogHandler::class;
        }
        $tarsLogHandler = new $tarsLogHandlerClass($communicatorConfig, 'tars.tarslog.LogObj', $level);

        $logger = app()->make('log');
        if ($logger instanceof Logger) {
            $logger->pushHandler($tarsLogHandler);
        } elseif (method_exists($logger, 'driver')) {
            $logger->driver()->pushHandler($tarsLogHandler);
        } else {
            $reflectionObj = new \ReflectionObject($logger);
            $monologProp = $reflectionObj->getProperty('monolog');
            $monologProp->setAccessible(true);
            $monolog = $monologProp->getValue($logger);

            $monolog->pushHandler($tarsLogHandler);
        }
    }
}
