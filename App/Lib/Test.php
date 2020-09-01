<?php
namespace App\Lib;
if (!defined('APP_FULL_PATH')) exit();

use Psr\Log\LoggerInterface;

class Test {
    private $logger;

    public function __construct(LoggerInterface $logger = null) {
        $this->logger = $logger;
    }

    public function hello() {
        if ($this->logger) {
            echo 'this is hello message! Test class.';
            $this->logger->info('Doing work');
        }
    }

}
