<?php declare(strict_types=1);

namespace App\Controller\User;

use App\Controller\BaseController;
use Ramsey\Uuid\Uuid;
use Slim\Http\Request;
use Slim\Http\Response;

class ChangePassword extends BaseUser
{
    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $this->setParams($request, $response, $args);

        $input = $this->getInput();

        // escape prefix
        $inputEscaped = $this->escapeInput($input);

        $token = $this->container->get('token');

        $user = $this->getUserService()->changePasswordByEmail($token['user_id'], $inputEscaped['currentPassword'], $inputEscaped['newPassword']);

        $resource = $this->buildUserResource($user, $request);
        return $this->response->withJson($resource, 200, JSON_PRETTY_PRINT);
    }
}
