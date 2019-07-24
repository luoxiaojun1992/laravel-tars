# TarsPHP与主流框架的集成

随着TarsPHP的发布，PHP语言也拥有了包含开发、运维、以及测试的一整套微服务解决方案。但是在实际的应用中，
还是需要考虑与现有技术栈的融合。如果是新开发的微服务，必须集成路由、ORM、日志等辅助类库。而对于已经存在的服务，
重新开发的成本也比较高。如果TarsPHP能够实现与主流框架的集成，会大大降低开发成本，帮助PHP开发者更快速地接入Tars
的基础能力。

## TarsPHP集成主流框架的思路

### 复用TarsPHP组件，包括tars-server、tars-log、tars-monitor、tars-registry等

复用的好处在于不需要重复实现tars的基础能力，减少开发量，避免重复踩坑，只需要解决与框架的适配问题。

```php
//把TarsPHP入口脚本的参数转换成Laravel框架Command脚本的参数
$_SERVER['argv'][0] = $argv[0] = __DIR__ .'/artisan';
$_SERVER['argv'][1] = $argv[1] = 'tars:entry';
$_SERVER['argv'][2] = $argv[2] = '--cmd=' . $cmd;
$_SERVER['argv'][3] = $argv[3] = '--config_path=' . $config_path;
$_SERVER['argc'] = $argc = count($_SERVER['argv']);
```

```php
//在Laravel框架的Command脚本里接收TarsPHP启动脚本传入的cmd和config路径，启动tars-server中的TarsCommand
public function handle()
{
    $cmd = $this->option('cmd');
    $cfg = $this->option('config_path');

    $class = new TarsCommand($cmd, $cfg);
    $class->run();
}
```

### 请求和响应的上下文转换

![Tars-Laravel HTTP请求过程](./tars-laravel-http-request.png)

### 合并Tars-Config与框架的配置项

### 集成Tars-Log到框架的日志组件中

tars-log组件自带了monolog handler，可以比较方便的集成到使用monolog作为日志引擎的框架，比如Laravel。
在没有使用monolog作为日志引擎的框架中，可以编写相应的handler来扩展日志输出的方式，比如Yii2 Log Target。

### 主动释放框架和PHP的全局资源，防止内存泄漏

```php
//释放Stat Cache
clearstatcache();
```

```php
//在Laravel框架中请求结束需要清除session、cookie等其他数据
if ($illuminateRequest->hasSession()) {
    $session = $illuminateRequest->getSession();
    if (is_callable([$session, 'clear'])) {
        $session->clear(); // @codeCoverageIgnore
    } else {
        $session->flush();
    }
}
...
```

```php
//在Yii2框架中请求结束需要清除session和缓存的日志数据
if ($app->has('session', true)) {
    $app->getSession()->close();
}

if($app->state == -1){
    $app->getLog()->logger->flush(true);
}
```

### 参考主流框架与Swoole结合的开源项目

借鉴相对成熟的集成Swoole的开源项目，能够更快地实现上面所说的几点。
1. Laravool: [https://github.com/garveen/laravoole](https://github.com/garveen/laravoole)
2. Yii2-Swoole: [https://github.com/tsingsun/yii2-swoole](https://github.com/tsingsun/yii2-swoole)
3. 更多的项目可以查看Swoole官方文档: [https://wiki.swoole.com/wiki/page/p-framework.html](https://wiki.swoole.com/wiki/page/p-framework.html)

## 需要特别注意的几点
1. 在开发中需要预防内存溢出。
2. 非协程框架不能使用协程。
3. 应用运行在PHP的cli模式下。

## 相关项目
1. TarsPHP: 
2. Tars-Laravel: 
3. Tars-Yii2: 

## 欢迎品尝和贡献代码
欢迎品尝TarsPHP、Tars-Laravel和Tars-Yii2，随手点个star，并通过提issue或PR的方式参与其中。