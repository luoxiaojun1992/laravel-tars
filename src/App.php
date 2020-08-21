<?php

namespace Lxj\Laravel\Tars;

class App
{
    public static $tarsDeployCfg;

    public static $app;

    public static function setTarsDeployCfg($tarsDeployCfg)
    {
        static::$tarsDeployCfg = $tarsDeployCfg;
    }

    public static function getTarsDeployCfg()
    {
        return static::$tarsDeployCfg;
    }

    public static function getApp()
    {
        if (static::$app) {
            return static::$app;
        }
        static::setTarsDeployCfg(config('tars.deploy_cfg'));
        static::$app = static::createApp();
        config(['tars.deploy_cfg' => static::getTarsDeployCfg()]);
        return static::$app;
    }

    public static function createApp()
    {
        return include app()->basePath('bootstrap/app.php');
    }
}
