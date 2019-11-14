<?php

namespace Lxj\Laravel\Tars\Route;

use Tars\route\RouteFactory;

class TarsRouteFactory extends RouteFactory
{
    public static function getRoute($routeName = '')
    {
        return new TarsRoute();
    }
}
