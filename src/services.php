<?php  
// 以namespace的方式,在psr4的框架下对代码进行加载  
return array(  
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
 ]
);
