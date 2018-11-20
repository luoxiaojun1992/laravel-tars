<?php

return [
    'registries' => [
//        [
//            'type' => 'kong',
//            'url' => env('KONG_UPSTREAM', ''),
//        ]
    ],

//    'tarsregistry' => env('TARS_REGISTRY', ''),

//    'log_level' => \Monolog\Logger::INFO,

//    'communicator_config_log_level' => 'INFO',

    'services' => [
        'namespaceName' => 'Lxj\Laravel\Tars\\',
        'monitorStoreConf' => [
            //'className' => Tars\monitor\cache\RedisStoreCache::class,
            //'config' => [
            // 'host' => '127.0.0.1',
            // 'port' => 6379,
            // 'password' => ':'
            //],
            'className' => Tars\monitor\cache\SwooleTableStoreCache::class,
            'config' => [
                'size' => 40960
            ]
        ],
    ],

    'proto' => [
        'appName' => 'PHPTest', //根据实际情况替换
        'serverName' => 'PHPHTTPServer', //根据实际情况替换
        'objName' => 'obj', //根据实际情况替换
    ],
];
