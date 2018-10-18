<?php

namespace Lxj\Laravel\Tars\Commands;

use Illuminate\Console\Command;
use Lxj\Laravel\Tars\Config;
use Lxj\Laravel\Tars\Registries\Registry;
use Lxj\Laravel\Tars\Util;
use Symfony\Component\Console\Input\InputOption;
use Tars\cmd\Command as TarsCommand;

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

    public function handle()
    {
        $cmd = $this->option('cmd');
        $cfg = $this->option('config_path');

        list($hostname, $port, $appName, $serverName) = Util::parseTarsConfig($cfg);

        $localConfig = config('tars');
        if (!empty($localConfig['tarsregistry'])) {
            $configtext = Config::fetch($localConfig['tarsregistry'], $appName, $serverName);
            if ($configtext) {
                $remoteConfig = json_decode($configtext, true);
                foreach ($remoteConfig as $configName => $configValue) {
                    app('config')->set($configName, array_merge(config($configName) ? : [], $configValue));
                }
            }
        }

        Registry::register($hostname, $port);

        $class = new TarsCommand($cmd, $cfg);
        $class->run();
    }
}
