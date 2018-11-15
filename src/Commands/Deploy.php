<?php

namespace Lxj\Laravel\Tars\Commands;

use Illuminate\Console\Command;

class Deploy extends Command
{
    protected $name = 'tars:deploy';

    public function handle()
    {
        $indexFileName = base_path('index.php');
        if (!is_executable($indexFileName)) {
            chmod($indexFileName, 0755);
        }

        \Tars\deploy\Deploy::run();
    }
}
