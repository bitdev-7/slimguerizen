<?php declare(strict_types=1);

namespace App\Controller\Transactions;

use App\Controller\BaseController;
use Ramsey\Uuid\Uuid;
use Slim\Http\Request;
use Slim\Http\Response;

class InitiateSpeculative extends BaseController
{
    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $this->setParams($request, $response, $args);

        return $this->response->withJson(['message' => 'no need to be implemented'], 200, JSON_PRETTY_PRINT);
    }
}
