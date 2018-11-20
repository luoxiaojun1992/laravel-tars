<?php
// 以namespace的方式,在psr4的框架下对代码进行加载  
$tarsConfig = include __DIR__ . '/config/tars.php';
return $tarsConfig['services'];
