<?php

namespace Lxj\Laravel\Tars;

use Tars\client\CommunicatorConfig;

class Config
{
    private static $communicatorConfig;

    public static function fetch($tarsregistry, $appName, $serverName, $logLevel = 'INFO')
    {
        $config = self::communicatorConfig($tarsregistry, $appName, $serverName, $logLevel);

        $configServant = new \Tars\config\ConfigServant($config);
        $configServant->loadConfig($appName, $serverName, 'tars', $configtext);
        return $configtext;
    }

    public static function communicatorConfig($tarsregistry, $appName, $serverName, $logLevel = 'INFO')
    {
        if (self::$communicatorConfig && self::$communicatorConfig instanceof CommunicatorConfig) {
            return self::$communicatorConfig;
        }

        $config = new \Tars\client\CommunicatorConfig(); //这里配置的是tars主控地址
        $config->setLocator($tarsregistry);
        $config->setModuleName($appName . '.' . $serverName); //主调名字用于显示再主调上报中。
        $config->setCharsetName("UTF-8"); //字符集
        $config->setLogLevel($logLevel);	//日志级别：`INFO`、`DEBUG`、`WARN`、`ERROR` 默认INFO
        $config->setSocketMode(2); //设置socket model为2 swoole tcp client，1为socket，3为swoole 协程 client

        return self::$communicatorConfig = $config;
    }
}
