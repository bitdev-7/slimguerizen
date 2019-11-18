<?php declare(strict_types=1);

namespace App\Controller;

use Slim\Container;
use Slim\Http\Request;
use Slim\Http\Response;

class DefaultController extends BaseController
{
    const API_VERSION = '0.0.2';

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function getHelp(Request $request, Response $response, array $args): Response
    {
        $this->setParams($request, $response, $args);
        $url = getenv('APP_DOMAIN');
        $endpoints = [
            'users' => $url . '/api/v1/users',
            'listings' => $url . '/api/v1/listings',
            'status' => $url . '/status',
            'this help' => $url . '',
        ];
        $message = [
            'endpoints' => $endpoints,
            'version' => self::API_VERSION,
            'timestamp' => time(),
        ];

        return $this->jsonResponse('success', $message, 200);
    }

    public function getStatus(Request $request, Response $response, array $args): Response
    {
        $this->setParams($request, $response, $args);
        $userService = $this->container->get('user_service');
        $listingService = $this->container->get('listing_service');
        $db = [
            'users' => count($userService->getUsers()),
            'listings' => count($listingService->getListings()),
        ];
        $status = [
            'db' => $db,
            'version' => self::API_VERSION,
            'timestamp' => time(),
        ];

        return $this->jsonResponse('success', $status, 200);
    }
}
