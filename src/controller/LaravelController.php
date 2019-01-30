<?php

namespace Lxj\Laravel\Tars\controller;

use Illuminate\Auth\AuthServiceProvider;
use Illuminate\Support\Facades\Facade;
use Lxj\Laravel\Tars\Controller;
use Lxj\Laravel\Tars\Request;
use Lxj\Laravel\Tars\Response;
use Lxj\Laravel\Tars\Trace;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LaravelController extends Controller
{
    public function actionRoute()
    {
        $request = $this->getRequest();
        $header = isset($request->data['header']) ? $request->data['header'] : [];
        foreach ($header as $k => $v) {
            $header[strtolower($k)] = $v;
        }
        $traceId = isset($header['x-trace-id']) ? $header['x-trace-id'] : null;

        $callback = function () use (&$traceId, $request) {
            try {
                clearstatcache();

                if (!isset($request->data)) {
                    $request->data = [];
                    $request->data['header'] = [];
                } elseif (is_array($request->data) && !isset($request->data['header'])) {
                    $request->data['header'] = [];
                }
                if (isset($request->data['header'])) {
                    $request->data['header']['x-trace-id'] = $traceId;
                }

                list($illuminateRequest, $illuminateResponse) = $this->handle();

                $this->terminate($illuminateRequest, $illuminateResponse);

                $this->clean($illuminateRequest);

                $this->response($illuminateResponse);
            } catch (\Exception $e) {
                $this->status(500);
                $this->sendRaw($e->getMessage() . '|' . $e->getTraceAsString());
            }
        };

        $tarsConfig = config('tars');
        $needSample = false;
        if (!empty($tarsConfig['trace']['zipkin_url'])) {
            if ($traceId) {
                $needSample = true;
            } elseif (!empty($tarsConfig['trace']['sample_rate'])) {
                if ($tarsConfig['trace']['sample_rate'] >= 1) {
                    $needSample = true;
                } else {
                    mt_srand(time());
                    if (mt_rand() / mt_getrandmax() <= $tarsConfig['trace']['sample_rate']) {
                        $needSample = true;
                    }
                }
            }
        }
        if ($needSample) {
            $serviceName = $tarsConfig['proto']['appName'] . '.' . $tarsConfig['proto']['serverName'] . '.' . $tarsConfig['proto']['objName'];
            $request = $this->getRequest();
            $server = isset($request->data['server']) ? $request->data['server'] : [];
            foreach ($server as $k => $v) {
                $server[strtolower($k)] = $v;
            }
            $spanName = isset($server['request_uri']) ? $server['request_uri'] : 'request';
            $traceId = $traceId ? : str_replace('-', '', \Ramsey\Uuid\Uuid::uuid4());
            Trace::span([
                'service_name' => $serviceName,
                'span_name' => $spanName,
                'zipkin_url' => $tarsConfig['trace']['zipkin_url'],
                'trace_id' => $traceId,
            ], $callback);
        } else {
            call_user_func($callback);
        }
    }

    private function handle()
    {
        ob_start();
        $isObEnd = false;

        $illuminateRequest = Request::make($this->getRequest())->toIlluminate();

        event('laravel.tars.requesting', [$illuminateRequest]);

        $illuminateResponse = app()->dispatch($illuminateRequest);

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
            $isObEnd = true;
        }

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
