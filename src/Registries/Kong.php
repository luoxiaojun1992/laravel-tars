<?php

namespace Lxj\Laravel\Tars\Registries;

use GuzzleHttp\Client;

class Kong
{
    public static function register($url, $hostname, $port)
    {
        (new Client())->request('POST', $url, [
            'form_params' => [
                'target' => $hostname . ':' . $port,
                'weight' => 100,
            ]
        ]);
    }

    public static function down($url, $hostname, $port)
    {
        (new Client())->request('POST', $url, [
            'form_params' => [
                'target' => $hostname . ':' . $port,
                'weight' => 0,
            ]
        ]);
    }
}
