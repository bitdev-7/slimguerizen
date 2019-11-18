<?php declare(strict_types=1);

namespace App\Controller\User;

use App\Controller\BaseController;
use App\Exception\UserException;
use App\Service\MailService;
use Ramsey\Uuid\Uuid;
use Slim\Http\Request;
use Slim\Http\Response;

class SendVerificationEmail extends BaseUser
{
    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $this->setParams($request, $response, $args);

        $token = $this->container->get('token');
        $user = $this->getUserService()->getUserByEmail($token['user_id']);

        if($user['emailVerified'] == 0) {
            $emailToVerify = $user['email'];
        } else {
            $emailToVerify = $user['pendingEmail'];
        }


        if(empty($emailToVerify)) {
            throw new UserException("The email address has already been verified.", 409);
        }

        // make email verification token
        $token = $this->getUserService()->makeVerifyEmailToken($user['uuid']);

        // send verification email (link: /verify-email?t=$token)
        $this->getMailService()->sendVerifyEmailMail($emailToVerify, $token);

        $resource = ['^ ',
            '~:data', ["^ ",
                '~:id', '~u' . Uuid::uuid4()->toString(),
                '~:type', '~:currentUser',
                ]
            ];

        return $this->response->withJson($resource, 200, JSON_PRETTY_PRINT);
    }
}
