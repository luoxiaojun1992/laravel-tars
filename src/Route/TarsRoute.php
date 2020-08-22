<?php

namespace Lxj\Laravel\Tars\Route;

use Illuminate\Auth\AuthServiceProvider;
use Illuminate\Contracts\Cookie\QueueingFactory;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\Facades\Facade;
use Lxj\Laravel\Tars\App;
use Lxj\Laravel\Tars\Boot;
use Lxj\Laravel\Tars\Util;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Tars\core\Request;
use Tars\core\Response;
use Tars\route\Route;

class TarsRoute implements Route
{
    protected static $app;

    public function dispatch(Request $request, Response $response)
    {
        Boot::handle();

        try {
            $this->clean();

            list($illuminateRequest, $illuminateResponse) = $this->handle($request);

            $this->terminate($illuminateRequest, $illuminateResponse);

            $this->response($response, $illuminateResponse);

            $this->clean($illuminateRequest);
        } catch (\Exception $e) {
            $response->status(500);
            $response->send($e->getMessage() . '|' . $e->getTraceAsString());
        }
    }

    protected function handle($tarsRequest)
    {
        if (ob_get_level() > 0) {
            ob_end_clean();
        }
        ob_start();
        $isObEnd = false;

        $illuminateRequest = \Lxj\Laravel\Tars\Request::make($tarsRequest)->toIlluminate();

        event('laravel.tars.requesting', [$illuminateRequest]);

        //Laravel App 创建完必须
        $application = $this->app();

        if (Util::isLumen()) {
            $illuminateResponse = $application->dispatch($illuminateRequest);
        } else {
            /** @var Kernel $kernel */
            $kernel = $application->make(Kernel::class);
            $illuminateResponse = $kernel->handle($illuminateRequest);
        }

        if (!($illuminateResponse instanceof BinaryFileResponse)) {
            $content = $illuminateResponse->getContent();
            if (strlen($content) === 0 && ob_get_length() > 0) {
                $illuminateResponse->setContent(ob_get_contents());
                ob_end_clean();
                $isObEnd = true;
            }
        }

        if (!$isObEnd) {
            ob_end_flush();
        }

        return [$illuminateRequest, $illuminateResponse];
    }

    protected function terminate($illuminateRequest, $illuminateResponse)
    {
        $application = $this->app();

        if (Util::isLumen()) {
            // Reflections
            $reflection = new \ReflectionObject($application);

            $middleware = $reflection->getProperty('middleware');
            $middleware->setAccessible(true);

            $callTerminableMiddleware = $reflection->getMethod('callTerminableMiddleware');
            $callTerminableMiddleware->setAccessible(true);

            if (count($middleware->getValue($application)) > 0) {
                $callTerminableMiddleware->invoke($application, $illuminateResponse);
            }
        } else {
            /** @var Kernel $kernel */
            $kernel = $application->make(Kernel::class);
            $kernel->terminate($illuminateRequest, $illuminateResponse);
        }

        event('laravel.tars.requested', [$illuminateRequest, $illuminateResponse]);
    }

    protected function clean($illuminateRequest = null)
    {
        clearstatcache();

        if ($illuminateRequest) {
            if ($illuminateRequest->hasSession()) {
                $session = $illuminateRequest->getSession();
                if (is_callable([$session, 'clear'])) {
                    $session->clear(); // @codeCoverageIgnore
                } else {
                    $session->flush();
                }
            }
        }

        $application = $this->app();

        if (Util::isLumen()) {
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
        } else {
            // Clean laravel cookie queue
            if ($application->has(QueueingFactory::class)) {
                $cookies = $application->make(QueueingFactory::class);
                foreach ($cookies->getQueuedCookies() as $name => $cookie) {
                    $cookies->unqueue($name);
                }
            }

            $loadedProviders = $application->getLoadedProviders();
            if (isset($loadedProviders[AuthServiceProvider::class])) {
                $application->register(AuthServiceProvider::class, true);
                Facade::clearResolvedInstance('auth');
            }
        }
    }

    protected function response($tarsResponse, $illuminateResponse)
    {
        \Lxj\Laravel\Tars\Response::make($illuminateResponse, $tarsResponse)->send();
    }

    protected function app()
    {
        return App::getApp();
    }
}
