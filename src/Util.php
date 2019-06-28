<?php

namespace Lxj\Laravel\Tars;

use Tars\Utils;

class Util
{
    protected static $app;

    public static function parseTarsConfig($cfg)
    {
        $hostname = gethostname();
        $tarsConfig = Utils::parseFile($cfg);
        $tarsServerConf = $tarsConfig['tars']['application']['server'];
        $port = $tarsServerConf['listen'][0]['iPort'];
        $appName = $tarsServerConf['app'];
        $serverName = $tarsServerConf['server'];
        return [$hostname, $port, $appName, $serverName];
    }

    public static function isLumen()
    {
        return class_exists('Laravel\Lumen\Application') && app() instanceof \Laravel\Lumen\Application;
    }

    public static function app()
    {
        if (self::$app) {
            return self::$app;
        }
        return self::$app = self::createApp();
    }

    public static function createApp()
    {
        return include app()->basePath('bootstrap/app.php');
    }
}
