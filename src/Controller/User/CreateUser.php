<?php declare(strict_types=1);

namespace App\Controller\User;

use Slim\Http\Request;
use Slim\Http\Response;

class CreateUser extends BaseUser
{
    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $this->setParams($request, $response, $args);
        $input = $this->getInput();
        // escape prefix
        $input_updated = $this->escapeInput($input);

        $user = $this->getUserService()->createUser($input_updated);

        $resource = $this->buildUserResource($user, $request);

        return $this->response->withJson($resource, 200, JSON_PRETTY_PRINT);
        // return $this->jsonResponse('success', $user, 201);
    }
}
