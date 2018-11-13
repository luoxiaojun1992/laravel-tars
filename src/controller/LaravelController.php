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

        ob_start();

        $illuminateResponse = $application->dispatch($illuminateRequest);

        $content = $illuminateResponse->getContent();
        if (strlen($content) === 0 && ob_get_length() > 0) {
            $illuminateResponse->setContent(ob_get_contents());
        }

        ob_end_clean();

        $this->terminate($illuminateResponse);

        $this->clean($illuminateRequest);

        Response::make($illuminateResponse, $this->getResponse())->send();
    }

    private function clean($illuminateRequest)
    {
        if ($illuminateRequest->hasSession()) {
            $session = $illuminateRequest->getSession();
            if (is_callable([$session, 'clear'])) {
                $session->clear(); // @codeCoverageIgnore
            } else {
                $session->flush();
            }
        }

        $application = app();

        // Clean laravel cookie queue
        if ($application->has('cookie')) {
            $cookieJar = $application->make('cookie');
            foreach ($cookieJar->getQueuedCookies() as $name => $cookie) {
                $cookieJar->unqueue($name);
            }
        }
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
