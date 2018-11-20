<?php
return array(
    'appName' => '{appName}', //根据实际情况替换
    'serverName' => '{serverName}', //根据实际情况替换
    'objName' => '{objName}', //根据实际情况替换
    'withServant' => true, //决定是服务端,还是客户端的自动生成,true为服务端
    'tarsFiles' => array(
        //根据实际情况填写
    ),
    'dstPath' => '../src/app/Tars/servant', //可替换，遵循PSR-4规则
    'namespacePrefix' => 'App\Tars\servant', //可替换，遵循PSR-4规则
);
