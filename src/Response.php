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

use Illuminate\Http\Response as IlluminateResponse;
use Tars\core\Response as TarsResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class Response
{
    /**
     * @var TarsResponse
     */
    protected $tarsResponse;

    /**
     * @var \Illuminate\Http\Response
     */
    protected $illuminateResponse;

    /**
     * Make a response.
     *
     * @param $illuminateResponse
     * @param TarsResponse $tarsResponse
     * @return static
     */
    public static function make($illuminateResponse, TarsResponse $tarsResponse)
    {
        return new static($illuminateResponse, $tarsResponse);
    }

    /**
     * Response constructor.
     *
     * @param mixed $illuminateResponse
     * @param TarsResponse $tarsResponse
     */
    public function __construct($illuminateResponse, TarsResponse $tarsResponse)
    {
        $this->setIlluminateResponse($illuminateResponse);
        $this->setTarsResponse($tarsResponse);
    }

    /**
     * Sends HTTP headers and content.
     *
     * @throws \InvalidArgumentException
     */
    public function send()
    {
        $this->sendHeaders();
        $this->sendContent();
    }

    /**
     * Sends HTTP headers.
     *
     * @throws \InvalidArgumentException
     */
    protected function sendHeaders()
    {
        $illuminateResponse = $this->getIlluminateResponse();

        /* RFC2616 - 14.18 says all Responses need to have a Date */
        if (! $illuminateResponse->headers->has('Date')) {
            $illuminateResponse->setDate(\DateTime::createFromFormat('U', time()));
        }

        // headers
        foreach ($illuminateResponse->headers->allPreserveCaseWithoutCookies() as $name => $values) {
            foreach ($values as $value) {
                $this->tarsResponse->header($name, $value);
            }
        }

        // status
        $this->tarsResponse->status($illuminateResponse->getStatusCode());

        // cookies
        foreach ($illuminateResponse->headers->getCookies() as $cookie) {
            $method = $cookie->isRaw() ? 'rawcookie' : 'cookie';

            $this->tarsResponse->resource->$method(
                $cookie->getName(), $cookie->getValue(),
                $cookie->getExpiresTime(), $cookie->getPath(),
                $cookie->getDomain(), $cookie->isSecure(),
                $cookie->isHttpOnly()
            );
        }
    }

    /**
     * Sends HTTP content.
     */
    protected function sendContent()
    {
        $illuminateResponse = $this->getIlluminateResponse();

        if ($illuminateResponse instanceof StreamedResponse) {
            $illuminateResponse->sendContent();
        } elseif ($illuminateResponse instanceof BinaryFileResponse) {
            $this->tarsResponse->resource->sendfile($illuminateResponse->getFile()->getPathname());
        } else {
            $this->tarsResponse->resource->end($illuminateResponse->getContent());
        }
    }

    /**
     * @param TarsResponse $tarsResponse
     * @return \HuangYi\Http\Server\Response
     */
    protected function setTarsResponse(TarsResponse $tarsResponse)
    {
        $this->tarsResponse = $tarsResponse;

        return $this;
    }

    /**
     * @return tarsResponse
     */
    public function getTarsResponse()
    {
        return $this->tarsResponse;
    }

    /**
     * @param mixed illuminateResponse
     * @return \HuangYi\Http\Server\Response
     */
    protected function setIlluminateResponse($illuminateResponse)
    {
        if (! $illuminateResponse instanceof SymfonyResponse) {
            $content = (string) $illuminateResponse;
            $illuminateResponse = new IlluminateResponse($content);
        }

        $this->illuminateResponse = $illuminateResponse;

        return $this;
    }

    /**
     * @return \Illuminate\Http\Response
     */
    public function getIlluminateResponse()
    {
        return $this->illuminateResponse;
    }
}
