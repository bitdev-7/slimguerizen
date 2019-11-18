<?php declare(strict_types=1);

namespace App\Controller\User;

use App\Controller\BaseController;
use App\Service\LogService;
use Ramsey\Uuid\Uuid;
use Slim\Http\Request;
use Slim\Http\Response;

class ChangeEmail extends BaseUser
{
    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $this->setParams($request, $response, $args);

        $token = $this->container->get('token');
        $email = $token['user_id'];

        $input = $this->getInput();
        $input_updated = $this->escapeInput($input);

        $user = $this->getUserService()->changeEmail($email, $input_updated['currentPassword'], $input_updated['email']);

        // update token when immediately updating
        if($user['email'] != $token['user_id']) {
            $token['user_id'] = $user['email'];

            // update token data
            $token = $this->updateAccessToken($token);

            LogService::dump($token, 'token updated', __FILE__, __LINE__);

            $this->container['token'] = $token;
        }

        $resource = $this->buildUserResource($user, $request);

        return $this->response->withJson($resource, 200, JSON_PRETTY_PRINT);

    }
}
