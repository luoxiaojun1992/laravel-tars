<?php

namespace Lxj\Laravel\Tars;

use Tars\client\CommunicatorConfig;

class Config
{
    private static $communicatorConfig;

    public static function fetch($deployConfigPath, $appName, $serverName)
    {
        $config = self::communicatorConfig($deployConfigPath);

        $configServant = new \Tars\config\ConfigServant($config);
        $configServant->loadConfig($appName, $serverName, 'tars', $configtext);
        return $configtext;
    }

    public static function communicatorConfig($deployConfigPath)
    {
        if (self::$communicatorConfig && self::$communicatorConfig instanceof CommunicatorConfig) {
            return self::$communicatorConfig;
        }

        $config = new \Tars\client\CommunicatorConfig(); //这里配置的是tars主控地址
        $config->init($deployConfigPath);
        $config->setCharsetName("UTF-8"); //字符集
        $config->setSocketMode(2); //设置socket model为2 swoole tcp client，1为socket，3为swoole 协程 client

        return self::$communicatorConfig = $config;
    }
}
