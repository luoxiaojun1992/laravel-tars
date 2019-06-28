<?php

namespace Lxj\Laravel\Tars\controller;

use Illuminate\Auth\AuthServiceProvider;
use Illuminate\Contracts\Cookie\QueueingFactory;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\Facades\Facade;
use Lxj\Laravel\Tars\Boot;
use Lxj\Laravel\Tars\Controller;
use Lxj\Laravel\Tars\Request;
use Lxj\Laravel\Tars\Response;
use Lxj\Laravel\Tars\Util;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LaravelController extends Controller
{
    private static $app;

    public function actionRoute()
    {
        Boot::handle();

        try {
            clearstatcache();

            list($illuminateRequest, $illuminateResponse) = $this->handle();

            $this->terminate($illuminateRequest, $illuminateResponse);

            $this->clean($illuminateRequest);

            $this->response($illuminateResponse);
        } catch (\Exception $e) {
            $this->status(500);
            $this->sendRaw($e->getMessage() . '|' . $e->getTraceAsString());
        }
    }

    private function handle()
    {
        ob_start();
        $isObEnd = false;

        $illuminateRequest = Request::make($this->getRequest())->toIlluminate();

        event('laravel.tars.requesting', [$illuminateRequest]);

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

    private function terminate($illuminateRequest, $illuminateResponse)
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
            $cookies = $application->make(QueueingFactory::class);
            foreach ($cookies->getQueuedCookies() as $name => $cookie) {
                $cookies->unqueue($name);
            }

            $loadedProviders = $application->getLoadedProviders();
            if (isset($loadedProviders[AuthServiceProvider::class])) {
                $application->register(AuthServiceProvider::class, true);
                Facade::clearResolvedInstance('auth');
            }
        }
    }

    private function response($illuminateResponse)
    {
        Response::make($illuminateResponse, $this->getResponse())->send();
    }

    public function app()
    {
        if (self::$app) {
            return self::$app;
        }
        return self::$app = $this->createApp();
    }

    public function createApp()
    {
        return include app()->basePath('bootstrap/app.php');
    }
}
