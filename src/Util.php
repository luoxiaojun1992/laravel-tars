<?php

namespace Lxj\Laravel\Tars;

use Tars\Utils;

class Util
{
    public static function parseTarsConfig($cfg)
    {
        $hostname = gethostname();
        $tarsConfig = Utils::parseFile($cfg);
        $tarsServerConf = $tarsConfig['tars']['application']['server'];
        $port = $tarsServerConf['listen'][0]['iPort'];
        return [$hostname, $port];
    }
}
