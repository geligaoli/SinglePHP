<?php


namespace App\Lib;


use Psr\Log\NullLogger;

class MyLog extends NullLogger
{
    public function log($level, $message, array $context = array())
    {
        echo $message;
    }
}