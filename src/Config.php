<?php

namespace Lxj\Laravel\Tars;

class Config
{
    public static function fetch($tarsregistry, $appName, $serverName)
    {
        $config = new \Tars\client\CommunicatorConfig(); //这里配置的是tars主控地址
        $config->setLocator($tarsregistry);
        $config->setModuleName($appName . '.' . $serverName); //主调名字用于显示再主调上报中。
        $config->setCharsetName("UTF-8"); //字符集
        $config->setSocketMode(2); //设置socket model为2 swoole tcp client，1为socket，3为swoole 协程 client

        $configServant = new \Tars\config\ConfigServant($config);
        $result = $configServant->loadConfig($appName, $serverName, 'tars', $configtext);
        return $configtext;
    }
}
