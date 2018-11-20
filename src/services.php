<?php
// 以namespace的方式,在psr4的框架下对代码进行加载
// 变量名这么长是为了防重复
$laravelTarsTmpTarsConfig = include __DIR__ . '/config/tars.php';
return $laravelTarsTmpTarsConfig['services'];
