<?php

namespace Lxj\Laravel\Tars;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\Facades\Facade;

class App
{
    public static $tarsDeployCfg;

    public static function setTarsDeployCfg($tarsDeployCfg)
    {
        static::$tarsDeployCfg = $tarsDeployCfg;
    }

    public static function getTarsDeployCfg()
    {
        return static::$tarsDeployCfg;
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return mixed
     */
    public static function getApp($request)
    {
        static::setTarsDeployCfg(config('tars.deploy_cfg'));

        $application = static::createApp();

        /** @var Kernel $kernel */
        $kernel = $application->make(Kernel::class);

        $request->enableHttpMethodParameterOverride();
        $application->instance('request', $request);
        Facade::clearResolvedInstance('request');
        $kernel->bootstrap();

        config(['tars.deploy_cfg' => static::getTarsDeployCfg()]);
        Boot::handle(true);

        return $application;
    }

    public static function createApp()
    {
        return include app()->basePath('bootstrap/app.php');
    }
}
