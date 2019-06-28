<?php
//php tarsCmd.php  conf restart

$config_path = $argv[1];
$pos = strpos($config_path, '--config=');
$config_path = substr($config_path, $pos + 9);
$cmd = strtolower($argv[2]);

if ($cmd === 'stop') {
    include_once __DIR__ . '/vendor/autoload.php';

    //Load Env
    if (method_exists(\Dotenv\Dotenv::class, 'create')) {
        Dotenv\Dotenv::create(__DIR__ . '/')->load();
    } else {
        (new Dotenv\Dotenv(__DIR__ . '/'))->load();
    }

    list($hostname, $port, $appName, $serverName) = \Lxj\Laravel\Tars\Util::parseTarsConfig($config_path);

    $localConfig = include_once __DIR__ . '/config/tars.php';

    \Lxj\Laravel\Tars\Registries\Registry::down($hostname, $port, $localConfig);

    $class = new \Tars\cmd\Command($cmd, $config_path);
    $class->run();
} else {
    $_SERVER['argv'][0] = $argv[0] = __DIR__ .'/artisan';
    $_SERVER['argv'][1] = $argv[1] = 'tars:entry';
    $_SERVER['argv'][2] = $argv[2] = '--cmd=' . $cmd;
    $_SERVER['argv'][3] = $argv[3] = '--config_path=' . $config_path;
    $_SERVER['argc'] = $argc = count($_SERVER['argv']);

    include_once __DIR__ . '/artisan';
}
