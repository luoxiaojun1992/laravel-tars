<?php

namespace Lxj\Laravel\Tars\Commands;

use Illuminate\Console\Command;

class Deploy extends Command
{
    protected $name = 'tars:deploy';

    public function handle()
    {
        \Tars\deploy\Deploy::run();
    }
}
