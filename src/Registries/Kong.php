<?php

namespace Lxj\Laravel\Tars\Registries;

use GuzzleHttp\Client;

class Kong
{
    public static function register($url, $hostname, $port)
    {
        self::performRequest('POST', $url, [
            'form_params' => [
                'target' => $hostname . ':' . $port,
                'weight' => 100,
            ]
        ]);
    }

    public static function down($url, $hostname, $port)
    {
        self::performRequest('POST', $url, [
            'form_params' => [
                'target' => $hostname . ':' . $port,
                'weight' => 0,
            ]
        ]);
    }

    private static function performRequest($method, $url, $params)
    {
        return (new Client())->request($method, $url, $params);
    }
}
