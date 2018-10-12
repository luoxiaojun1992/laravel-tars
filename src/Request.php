<?php

/*
 * This file is part of the huang-yi/laravel-swoole-http package.
 *
 * (c) Huang Yi <coodeer@163.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Lxj\Laravel\Tars;

use Illuminate\Http\Request as IlluminateRequest;
use Tars\core\Request as TarsRequest;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

class Request
{
    /**
     * @var \Illuminate\Http\Request
     */
    protected $illuminateRequest;

    /**
     * Make a request.
     *
     * @param TarsRequest $tarsRequest
     * @return \HuangYi\Http\Server\Request
     */
    public static function make(TarsRequest $tarsRequest)
    {
        list($get, $post, $cookie, $files, $server, $content)
            = self::toIlluminateParameters($tarsRequest);

        return new static($get, $post, $cookie, $files, $server, $content);
    }

    /**
     * Request constructor.
     *
     * @param array $get
     * @param array $post
     * @param array $cookie
     * @param array $files
     * @param array $server
     * @param string $content
     * @throws \LogicException
     */
    public function __construct(array $get, array $post, array $cookie, array $files, array $server, $content = null)
    {
        $this->createIlluminateRequest($get, $post, $cookie, $files, $server, $content);
    }

    /**
     * Create Illuminate Request.
     *
     * @param array $get
     * @param array $post
     * @param array $cookie
     * @param array $files
     * @param array $server
     * @param string $content
     * @return \Illuminate\Http\Request
     * @throws \LogicException
     */
    protected function createIlluminateRequest($get, $post, $cookie, $files, $server, $content = null)
    {
        IlluminateRequest::enableHttpMethodParameterOverride();

        /*
        |--------------------------------------------------------------------------
        | Copy from \Symfony\Component\HttpFoundation\Request::createFromGlobals().
        |--------------------------------------------------------------------------
        |
        | With the php's bug #66606, the php's built-in web server
        | stores the Content-Type and Content-Length header values in
        | HTTP_CONTENT_TYPE and HTTP_CONTENT_LENGTH fields.
        |
        */

        if ('cli-server' === PHP_SAPI) {
            if (array_key_exists('HTTP_CONTENT_LENGTH', $server)) {
                $server['CONTENT_LENGTH'] = $server['HTTP_CONTENT_LENGTH'];
            }
            if (array_key_exists('HTTP_CONTENT_TYPE', $server)) {
                $server['CONTENT_TYPE'] = $server['HTTP_CONTENT_TYPE'];
            }
        }

        $request = new SymfonyRequest($get, $post, [], $cookie, $files, $server, $content);

        if (0 === strpos($request->headers->get('CONTENT_TYPE'), 'application/x-www-form-urlencoded')
            && in_array(strtoupper($request->server->get('REQUEST_METHOD', 'GET')), array('PUT', 'DELETE', 'PATCH'))
        ) {
            parse_str($request->getContent(), $data);
            $request->request = new ParameterBag($data);
        }

        $this->illuminateRequest = IlluminateRequest::createFromBase($request);
    }

    /**
     * @return \Illuminate\Http\Request
     */
    public function toIlluminate()
    {
        return $this->getIlluminateRequest();
    }

    /**
     * @return \Illuminate\Http\Request
     */
    public function getIlluminateRequest()
    {
        return $this->illuminateRequest;
    }

    /**
     * Transforms request parameters.
     *
     * @param TarsRequest $request
     * @return array
     */
    protected static function toIlluminateParameters(TarsRequest $request)
    {
        $get = isset($request->data['get']) ? $request->data['get'] : [];
        $post = $request->data['post'] ? : [];
        $cookie = isset($request->data['cookie']) ? $request->data['cookie'] : [];
        $files = isset($request->data['files']) ? $request->data['files'] : [];
        $header = isset($request->data['header']) ? $request->data['header'] : [];
        $server = isset($request->data['server']) ? $request->data['server'] : [];
        $server = self::transformServerParameters($server, $header);
        $content = $request->data['post'] ? 
            (is_array($request->data['post']) ? http_build_query($request->data['post']) : $request->data['post']) : 
            null;

        return [$get, $post, $cookie, $files, $server, $content];
    }

    /**
     * Transforms $_SERVER array.
     *
     * @param array $server
     * @param array $header
     * @return array
     */
    protected static function transformServerParameters(array $server, array $header)
    {
        $__SERVER = [];

        foreach ($server as $key => $value) {
            $key = strtoupper($key);
            $__SERVER[$key] = $value;
        }

        foreach ($header as $key => $value) {
            $key = str_replace('-', '_', $key);
            $key = strtoupper($key);

            if (! in_array($key, ['REMOTE_ADDR', 'SERVER_PORT', 'HTTPS'])) {
                $key = 'HTTP_' . $key;
            }

            $__SERVER[$key] = $value;
        }

        return $__SERVER;
    }
}
