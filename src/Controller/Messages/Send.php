<?php declare(strict_types=1);

namespace App\Controller\Messages;

use App\Controller\BaseController;
use App\Exception\ListingException;
use App\Service\LogService;
use Slim\Http\Request;
use Slim\Http\Response;

class Send extends BaseMessage
{
    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $this->setParams($request, $response, $args);
        $input = $this->getInput();
        // escape prefix
        $input_updated = $this->escapeInput($input);

        LogService::dump($input_updated, "input_updated");

        $token = $this->container->get('token');
        $user = $this->getUserService()->getUserByEmail($token['user_id']);

        // get transaction by id
        $transactionId = $input_updated['transactionId'];
        $transaction = $this->getListingService()->getTransaction($transactionId);
        if($transaction['customerId'] != $user['uuid'] && $transaction['providerId'] != $user['uuid']) {
            throw new ListingException("invalid transaction", 404);
        }

        $input_updated['senderId'] = $user['uuid'];
        $input_updated['customerId'] = $transaction['customerId'];
        $input_updated['providerId'] = $transaction['providerId'];

        $input_updated['listingId'] = $transaction['params']['listingId'];

        $message = $this->getListingService()->addMessage($input_updated);

        LogService::dump($message, "avail");

        // build response
        $response = [
            '^ ',
            '~:data', [],
        ];

        $resource = $this->buildMessageResource($message);

        $response[2] = $resource;

        return $this->response->withJson($response, 200, JSON_PRETTY_PRINT);
    }
}
