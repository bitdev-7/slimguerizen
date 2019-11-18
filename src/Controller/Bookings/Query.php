<?php declare(strict_types=1);

namespace App\Controller\Bookings;

use App\Controller\BaseController;
use Ramsey\Uuid\Uuid;
use Slim\Http\Request;
use Slim\Http\Response;

class Query extends BaseController
{
    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $this->setParams($request, $response, $args);

        // only for demo
        $resp = [
            "^ ",
            "~:data", [],
            "~:meta", [ "^ ",
                "~:totalItems", 0,
                "~:totalPages", 0,
                "~:page", 1,
                "~:perPage", 1,
            ],
        ];

        return $this->response->withJson($resp, 200, JSON_PRETTY_PRINT);
    }
}
