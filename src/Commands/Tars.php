<?php

namespace Lxj\Laravel\Tars\Commands;

use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Tars\cmd\Command as TarsCommand;
use Tars\Utils;

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

        $class = new TarsCommand($cmd, $cfg);
        $class->run();

        $hostname = gethostname();
        $tarsConfig = Utils::parseFile($cfg);
        $tarsServerConf = $tarsConfig['tars']['application']['server'];
        $port = $tarsServerConf['listen'][0]['iPort'];
        $this->register($hostname, $port);
    }

    private function register($hostname, $port)
    {
        $tarsDriverConfig = config('tars');

        foreach($tarsDriverConfig['registries'] as $registry) {
            if ($registry['type'] === 'kong') {
                (new Client())->request('POST', $registry['url'], [
                    'form_params' => [
                        'target' => $hostname . ':' . $port,
                        'weight' => 100,
                    ]
                ]);
            }
        }
    }
}
