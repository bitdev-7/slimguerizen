<?php declare(strict_types=1);

namespace App\Controller\User;

use App\Controller\BaseController;
use Ramsey\Uuid\Uuid;
use Slim\Http\Request;
use Slim\Http\Response;

class VerifyEmail extends BaseUser
{
    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $this->setParams($request, $response, $args);

        $token = $this->container->get('token');
        $user = $this->getUserService()->getUserByEmail($token['user_id']);

        $input = $this->getInput();
        $input_updated = $this->escapeInput($input);
        $veri_token = $input_updated['verificationToken'];

        $user = $this->getUserService()->verifyEmail($user['uuid'], $veri_token);

        // update token when updating
        if($user['email'] != $token['user_id']) {
            $token['user_id'] = $user['email'];
            // update token data
            $token = $this->updateAccessToken($token);
            $this->container['token'] = $token;
        }

        $resp = $this->buildUserResource($user, $request);

        return $this->response->withJson($resp, 200, JSON_PRETTY_PRINT);
    }
}
