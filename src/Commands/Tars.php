<?php

namespace Lxj\Laravel\Tars\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Lxj\Laravel\Tars\Config;
use Lxj\Laravel\Tars\Registries\Registry;
use Lxj\Laravel\Tars\Util;
use Monolog\Logger;
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
            $logLevel = isset($localConfig['log_level']) ? $localConfig['log_level'] : Logger::INFO;
            $communicatorConfigLogLevel = isset($localConfig['communicator_config_log_level']) ? $localConfig['communicator_config_log_level'] : 'INFO';

            $this->fetchConfig($localConfig['tarsregistry'], $appName, $serverName, $communicatorConfigLogLevel);

            $this->setTarsLog($localConfig['tarsregistry'], $appName, $serverName, $logLevel, $communicatorConfigLogLevel);
        }

        Registry::register($hostname, $port);

        $class = new TarsCommand($cmd, $cfg);
        $class->run();
    }

    private function fetchConfig($tarsregistry, $appName, $serverName, $logLevel = 'INFO')
    {
        $configtext = Config::fetch($tarsregistry, $appName, $serverName, $logLevel);
        if ($configtext) {
            $remoteConfig = json_decode($configtext, true);
            foreach ($remoteConfig as $configName => $configValue) {
                app('config')->set($configName, array_merge(config($configName) ?: [], $configValue));
            }
        }
    }

    private function setTarsLog($tarsregistry, $appName, $serverName, $level = Logger::INFO, $communicatorConfigLogLevel = 'INFO')
    {
        $config = Config::communicatorConfig($tarsregistry, $appName, $serverName, $communicatorConfigLogLevel);

        Log::driver()->pushHandler(new \Tars\log\handler\TarsHandler($config, 'tars.tarslog.LogObj', $level));
    }
}
