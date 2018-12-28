<?php

namespace Lxj\Laravel\Tars;

class Trace
{
    public static function span($serviceName, $spanName, $zipkinUrl, $callback, $traceId = null)
    {
        $startTime = (int)((float) (new \DateTime('now'))->format('U.u') * 1000 * 1000);

        call_user_func($callback);

        $endTime = (int)((float) (new \DateTime('now'))->format('U.u') * 1000 * 1000);

        $spans = array (
            0 =>
                array (
                    'traceId' => str_pad($traceId ? : str_replace('-', '', \Ramsey\Uuid\Uuid::uuid4()), 32, '0', STR_PAD_LEFT),
                    'name' => $spanName,
                    'parentId' => null,
                    'id' => str_pad(dechex(mt_rand()), 16, '0', STR_PAD_LEFT),
                    'timestamp' => $startTime,
                    'duration' => $endTime - $startTime,
                    'debug' => false,
                    'shared' => true,
                    'localEndpoint' =>
                        array (
                            'serviceName' => $serviceName,
                        ),
                    'tags' => new \stdClass(),
                ),
        );

        $json = json_encode($spans);
        $contextOptions = [
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/json',
                'content' => $json
            ]
        ];

        $context = stream_context_create($contextOptions);
        file_get_contents($zipkinUrl, false, $context);
    }
}
