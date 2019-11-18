<?php declare(strict_types=1);

namespace App\Controller\User;

use App\Service\LogService;
use Ramsey\Uuid\Uuid;
use Slim\Http\Request;
use Slim\Http\Response;

class ShowUser extends BaseUser
{
    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $this->setParams($request, $response, $args);

        $token = $this->container->get('token');
        LogService::dump($token, "token");

        $user = $this->getUserService()->getUserByEmail($token['user_id']);

        LogService::dump($user,"user");

        $resource = $this->buildUserResource($user, $request);

        LogService::dump($resource,"resource");

        return $this->response->withJson($resource, 200, JSON_PRETTY_PRINT);
    }

    public function showPublic(Request $request, Response $response, array $args): Response
    {
        $this->setParams($request, $response, $args);
        $uuid = $this->request->getParam('id');

        $user = $this->getUserService()->getUserByUuid($uuid);

        $resource = $this->buildPublicResource($user, $request);

        return $this->response->withJson($resource, 200, JSON_PRETTY_PRINT);
    }
}
