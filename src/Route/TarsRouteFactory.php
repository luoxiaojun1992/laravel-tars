<?php

namespace Lxj\Laravel\Tars\Route;

class TarsRouteFactory
{
    public static function getRoute($routeName = '')
    {
        return new TarsRoute();
    }
}
