<?php declare(strict_types=1);

namespace App\Service;

use App\Exception\UserException;
use App\Repository\UserRepository;
use \Firebase\JWT\JWT;
use Ramsey\Uuid\Uuid;

class LogService
{
    protected static $logger;

    public static function setLogger($logger) {
        static::$logger = $logger;
    }

    public static function getLogger() {
        return static::$logger;
    }

    public static function dump($object, $name = '', $file = '_', $line = 0) {
        static::$logger->info("$file:$line $name=" . json_encode($object));
    }

    public static function log($content, $file = '_', $line = 0) {
        static::$logger->info("$file:$line " . $content);
    }

}
