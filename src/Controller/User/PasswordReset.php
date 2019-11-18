<?php declare(strict_types=1);

namespace App\Controller\User;

use App\Controller\BaseController;
use App\Service\MailService;
use Ramsey\Uuid\Uuid;
use Slim\Http\Request;
use Slim\Http\Response;

class PasswordReset extends BaseUser
{
    public function request(Request $request, Response $response, array $args): Response
    {
        $this->setParams($request, $response, $args);

        $input = $this->getInput();
        // escape prefix
        $input_updated = $this->escapeInput($input);

        $email = $input_updated['email'];
        if(!empty($email)) {
            // make reset password token
            $token = $this->getUserService()->makeResetPasswordToken($email);

            // send reset password email (link: /reset-password?t=$token&e=$email)
            $this->getMailService()->sendPasswordResetMail($email, $token);
        }

        $resp = ["^ ",
            "~:data", ["^ ",
                "~:id","~u" . Uuid::uuid4()->toString(),
                "~:type","~:passwordReset",
                ]
            ];

        return $this->response->withJson($resp, 200, JSON_PRETTY_PRINT);
    }

    public function reset(Request $request, Response $response, array $args): Response
    {
        $this->setParams($request, $response, $args);

        $input = $this->getInput();
        // escape prefix
        $input_updated = $this->escapeInput($input);

        $email = $input_updated['email'];
        $token = $input_updated['passwordResetToken'];
        $newPwd = $input_updated['newPassword'];

        if(!empty($email)) {
            // reset password
            $this->getUserService()->resetPassword($email, $token, $newPwd);
        }

        $resp = ["^ ",
            "~:data", ["^ ",
                "~:id","~u" . Uuid::uuid4()->toString(),
                "~:type","~:passwordReset",
            ]
        ];

        return $this->response->withJson($resp, 200, JSON_PRETTY_PRINT);
    }
}
