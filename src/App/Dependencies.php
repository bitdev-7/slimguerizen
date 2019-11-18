<?php declare(strict_types=1);

use Psr\Container\ContainerInterface;
use App\Service\LogService;
use Illuminate\Database\Capsule\Manager as Capsule;

$container = $app->getContainer();

// monolog
$container['logger'] = function ($c) {
    $settings = $c->get('settings')['logger'];
    $logger = new Monolog\Logger($settings['name']);
    $logger->pushProcessor(new Monolog\Processor\UidProcessor());
    $logger->pushHandler(new Monolog\Handler\StreamHandler($settings['path'], $settings['level']));

    LogService::setLogger($logger);
    return $logger;
};

$container['db'] = function (ContainerInterface $c): PDO {
    $db = $c->get('settings')['db'];
    $database = sprintf('pgsql:host=%s;dbname=%s', $db['host'], $db['database']);

    $pdo = new PDO($database, $db['username'], $db['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

    return $pdo;
};

$container['db_capsule'] = function (ContainerInterface $c) : Capsule {
    $capsule = new Capsule;
    $capsule->addConnection($c['settings']['db']);

    $capsule->setAsGlobal();
    $capsule->bootEloquent();

    return $capsule;
};






