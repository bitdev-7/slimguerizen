<?php declare(strict_types=1);

namespace App\Controller\User;

use App\Controller\BaseController;
use App\Service\LogService;
use Ramsey\Uuid\Uuid;
use Slim\Http\Request;
use Slim\Http\Response;

class UpdateProfile extends BaseUser
{
    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $this->setParams($request, $response, $args);

        $token = $this->container->get('token');
        $email = $token['user_id'];

        $user = $this->getUserService()->getUserByEmail($email);

        $input = $this->getInput();
        $input_updated = $this->escapeInput($input);

        LogService::dump($input_updated, "input_updated");

        $user = $this->getUserService()->updateUser($input_updated, $user['uuid']);

        LogService::dump($user, "user");

        $resource = $this->buildUserResource($user, $request);


        return $this->response->withJson($resource, 200, JSON_PRETTY_PRINT);
    }
}
