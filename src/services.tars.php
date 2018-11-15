<?php  
// 以namespace的方式,在psr4的框架下对代码进行加载  
return array(
    'home-api' => '\App\Tars\servant\{appName}\{serverName}\{objName}\{servantName}', //根据实际情况替换，遵循PSR-4即可，与tars.proto.php配置一致
    'home-class' => '\App\Tars\impl\{servantImplName}', //根据实际情况替换，遵循PSR-4即可
);
