<?php

namespace Lxj\Laravel\Tars\controller;

use Illuminate\Auth\AuthServiceProvider;
use Illuminate\Support\Facades\Facade;
use Lxj\Laravel\Tars\Controller;
use Lxj\Laravel\Tars\Request;
use Lxj\Laravel\Tars\Response;

class LaravelController extends Controller
{
    public function actionRoute()
    {
        clearstatcache();

        list($illuminateRequest, $illuminateResponse) = $this->handle();

        $this->terminate($illuminateRequest, $illuminateResponse);

        $this->clean($illuminateRequest);

        $this->response($illuminateResponse);
    }

    private function handle()
    {
        ob_start();

        $illuminateRequest = Request::make($this->getRequest())->toIlluminate();

        event('laravel.tars.requesting', [$illuminateRequest]);

        $illuminateResponse = app()->dispatch($illuminateRequest);

        $content = $illuminateResponse->getContent();
        if (strlen($content) === 0 && ob_get_length() > 0) {
            $illuminateResponse->setContent(ob_get_contents());
        }

        ob_end_clean();

        return [$illuminateRequest, $illuminateResponse];
    }

    private function terminate($illuminateRequest, $illuminateResponse)
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

        event('laravel.tars.requested', [$illuminateRequest, $illuminateResponse]);
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

        // Reflections
        $reflection = new \ReflectionObject($application);
        $loadedProviders = $reflection->getProperty('loadedProviders');
        $loadedProviders->setAccessible(true);
        $loadedProvidersValue = $loadedProviders->getValue($application);
        if (array_key_exists(AuthServiceProvider::class, $loadedProvidersValue)) {
            unset($loadedProvidersValue[AuthServiceProvider::class]);
            $loadedProviders->setValue($application, $loadedProvidersValue);
            $application->register(AuthServiceProvider::class);
            Facade::clearResolvedInstance('auth');
        }
    }

    private function response($illuminateResponse)
    {
        Response::make($illuminateResponse, $this->getResponse())->send();
    }
}
