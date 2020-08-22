<?php

namespace Lxj\Laravel\Tars;

use Illuminate\Contracts\Http\Kernel;

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
        $application = static::$app;
        $application->make(Kernel::class);
        config(['tars.deploy_cfg' => static::getTarsDeployCfg()]);
        Boot::handle(true);
        return static::$app;
    }

    public static function createApp()
    {
        return include app()->basePath('bootstrap/app.php');
    }
}
