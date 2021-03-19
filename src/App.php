<?php

namespace Lxj\Laravel\Tars;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Facade;

class App
{
    public static $app;

    public static $tarsDeployCfg;

    public static function setTarsDeployCfg($tarsDeployCfg)
    {
        static::$tarsDeployCfg = $tarsDeployCfg;
    }

    public static function getTarsDeployCfg()
    {
        return static::$tarsDeployCfg;
    }

    public static function bootLaravelKernel($app, $request)
    {
        /** @var Kernel $kernel */
        $kernel = $app->make(Kernel::class);

        $request->enableHttpMethodParameterOverride();
        $app->instance('request', $request);
        Facade::clearResolvedInstance('request');
        $kernel->bootstrap();
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return mixed
     */
    public static function getApp($request)
    {
        if (!is_null(static::$app)) {
            static::bootLaravelKernel(static::$app, $request);
            return static::$app;
        }

        static::setTarsDeployCfg(config('tars.deploy_cfg'));

        $oldApp = app();

        $application = static::createApp();
        static::$app = $application;

        $oldApp->flush();
        Facade::clearResolvedInstances();

        if (!Util::isLumen()) {
            static::bootLaravelKernel($application, $request);
        } else {
            Facade::setFacadeApplication($application);
        }

        config(['tars.deploy_cfg' => static::getTarsDeployCfg()]);
        Boot::handle(true);

        return $application;
    }

    public static function createApp()
    {
        return include app()->basePath('bootstrap/app.php');
    }
}
