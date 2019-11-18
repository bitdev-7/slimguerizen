<?php declare(strict_types=1);

use App\Service\UserService;
use App\Service\ListingService;
use App\Repository\UserRepository;
use App\Repository\ListingRepository;
use App\Handler\ApiError;
use Psr\Container\ContainerInterface;
use OAuth2\Server;
use OAuth2\Storage;
use OAuth2\GrantType;

$container = $app->getContainer();

$container['errorHandler'] = function (): ApiError {
    return new ApiError;
};

$container['user_service'] = function (ContainerInterface $container): UserService {
    return new UserService($container->get('user_repository'));
};

$container['user_repository'] = function (ContainerInterface $container): UserRepository {
    return new UserRepository($container);
};

$container['listing_service'] = function (ContainerInterface $container): ListingService {
    return new ListingService($container->get('listing_repository'));
};

$container['listing_repository'] = function (ContainerInterface $container): ListingRepository {
    return new ListingRepository($container);
};

$container['auth_server'] = function(ContainerInterface $container) : Server {
    $logger = $container->get('logger');

    $pdo = $container->get('db');
    $config = $container->get('settings')['auth_server'];

    $storage = new Storage\Pdo($pdo, [
            'user_table' => $config['user_table'],    // redefine the default user table name
            'access_token_table' => $config['access_token_table'],
        ]
    );

    return new OAuth2\Server(
        $storage,
        [
            'access_lifetime' => $config['access_lifetime'],
        ],
        [
            new \App\Service\MyClientCredentials($storage, ['allow_public_clients' => true]),
            new GrantType\AuthorizationCode($storage),
            new GrantType\UserCredentials($storage),
        ]
    );
};

$container['mailer'] = function (ContainerInterface $container) : \App\Service\MailService {
    $config = $container->get('settings')['mailer'];

    $config = array_merge(["app_domain" => $container->get('settings')['app_domain']], $config);

    return new \App\Service\MailService($config);
};
