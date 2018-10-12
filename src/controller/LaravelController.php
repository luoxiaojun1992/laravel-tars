<?php

namespace Lxj\Laravel\Tars\controller;

use Lxj\Laravel\Tars\Controller;
use Lxj\Laravel\Tars\Request;
use Lxj\Laravel\Tars\Response;

class LaravelController extends Controller
{
    public function actionRoute()
    {
        $illuminateRequest = Request::make($this->getRequest())->toIlluminate();

        $application = app();

        $illuminateResponse = $application->dispatch($illuminateRequest);

        $this->terminate($illuminateResponse);

        Response::make($illuminateResponse, $this->getResponse())->send();
    }

    private function terminate($illuminateResponse)
    {
        $application = app();

        // Reflections
        $reflection = new \ReflectionObject($application);

        $middleware = $reflection->getProperty('middleware');
        $middleware->setAccessible(true);

        $callTerminableMiddleware = $reflection->getMethod('callTerminableMiddleware');
        $callTerminableMiddleware->setAccessible(true);

        if (count($middleware->getValue($application)) > 0) {
            $callTerminableMiddleware->invoke($application, $illuminateResponse);
        }
    }
}
