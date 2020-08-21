<?php

namespace Lxj\Laravel\Tars\Commands;

use Illuminate\Console\Command;
use Lxj\Laravel\Tars\Registries\Registry;
use Lxj\Laravel\Tars\Route\TarsRouteFactory;
use Lxj\Laravel\Tars\Util;
use Symfony\Component\Console\Input\InputOption;
use Tars\cmd\Command as TarsCommand;
use Tars\route\RouteFactory;

class Tars extends Command
{
    protected $name = 'tars:entry';

    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this->addOption('cmd', 'cmd', InputOption::VALUE_REQUIRED);
        $this->addOption('config_path', 'cfg', InputOption::VALUE_REQUIRED);
    }

    public function setEnv($cfg)
    {
        $envPath = base_path() . DIRECTORY_SEPARATOR . '.env';
        $lines = collect(file($envPath, FILE_IGNORE_NEW_LINES));
        $lines->transform(function ($item) use ($cfg) {

            foreach ($cfg as $k => $v) {
                if (stripos($item, $k) !== false) {
                    return "{$k}={$v}";
                }
            }
            return $item;
        });

        $content = implode( PHP_EOL, $lines->toArray());

        file_put_contents($envPath, $content);
    }

    public function handle()
    {
        $cmd = $this->option('cmd');
        $cfg = $this->option('config_path');

        class_alias(TarsRouteFactory::class, RouteFactory::class);

        list($hostname, $port, $appName, $serverName) = Util::parseTarsConfig($cfg);

        config(['tars.deploy_cfg' => $cfg]);

        $this->setEnv([
            'TARS_DEPLOY_CONFIG' => $cfg
        ]);
        
        Registry::register($hostname, $port);

        $class = new TarsCommand($cmd, $cfg);
        $class->run();
    }
}
