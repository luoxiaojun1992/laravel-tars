<?php

return [
//    'tarsregistry' => env('TARS_REGISTRY', ''),

//    'log_level' => \Monolog\Logger::INFO,

//    'communicator_config_log_level' => 'INFO',

    'services' => [
        'home-api' => '\App\Tars\servant\PHPTest\PHPServer\obj\TestTafServiceServant', //根据实际情况替换，遵循PSR-4即可，与tars.proto.php配置一致
        'home-class' => '\App\Tars\impl\TestTafServiceImpl', //根据实际情况替换，遵循PSR-4即可
    ],

    'proto' => [
        'appName' => 'PHPTest', //根据实际情况替换
        'serverName' => 'PHPServer', //根据实际情况替换
        'objName' => 'obj', //根据实际情况替换
        'withServant' => true, //决定是服务端,还是客户端的自动生成,true为服务端
        'tarsFiles' => array(
            //根据实际情况填写
            './example.tars',
        ),
        'dstPath' => '../src/app/Tars/servant', //可替换，遵循PSR-4规则
        'namespacePrefix' => 'App\Tars\servant', //可替换，遵循PSR-4规则
    ],
];
