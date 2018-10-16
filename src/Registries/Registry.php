<?php

namespace Lxj\Laravel\Tars\Registries;

class Registry
{
    public static function register($hostname, $port)
    {
        $tarsDriverConfig = config('tars');

        foreach ($tarsDriverConfig['registries'] as $registry) {
            if ($registry['type'] === 'kong') {
                Kong::register($registry['url'], $hostname, $port);
            }
        }
    }

    public static function down($hostname, $port, $tarsDriverConfig)
    {
        foreach ($tarsDriverConfig['registries'] as $registry) {
            if ($registry['type'] === 'kong') {
                Kong::down($registry['url'], $hostname, $port);
            }
        }
    }
}
