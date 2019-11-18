<?php declare(strict_types=1);

namespace App\Controller\Transactions;

use App\Service\LogService;
use Ramsey\Uuid\Uuid;
use Slim\Http\Request;
use Slim\Http\Response;

class Initiate extends BaseTransaction
{
    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $this->setParams($request, $response, $args);

        $input = $this->getInput();

        $input_updated = $this->escapeInput($input);
        LogService::dump($input_updated, "input_updated");

        $token = $this->container->get('token');
        $user = $this->getUserService()->getUserByEmail($token['user_id']);
        $input_updated['customerId'] = $user['uuid'];

        $listingId = $input_updated['params']['listingId'];
        $listing = $listing = $this->getListingService()->searchListingByUuid($listingId);
        $input_updated['providerId'] = $listing['authorId'];

        if($input_updated['customerId'] == $input_updated['providerId']) {
            throw new ListingException("invalid transaction parameter", 404);
        }

        $response = [
            '^ ',
            '~:data', [],
        ];

        $transaction = $this->getListingService()->addTransaction($input_updated);

        $resource = $this->buildTransactionResource($transaction);
        $response[2] = $resource['data'];

        return $this->response->withJson($response, 200, JSON_PRETTY_PRINT);

    }
}
