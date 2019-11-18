<?php declare(strict_types=1);

namespace App\Controller\Transactions;

use App\Controller\BaseController;
use App\Exception\ListingException;
use Ramsey\Uuid\Uuid;
use Slim\Http\Request;
use Slim\Http\Response;

class Show extends BaseTransaction
{
    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $this->setParams($request, $response, $args);

        $token = $this->container->get('token');
        $input = $this->request->getParams();

        $user = $this->getUserService()->getUserByEmail($token['user_id']);

        $transaction = $this->getListingService()->getTransaction($input['id']);

        // check participate
        if($transaction['customerId'] != $user['uuid'] && $transaction['providerId'] != $user['uuid']) {
            throw new ListingException("cannot access transaction", 404);
        }

        // build response
        $response = [
            '^ ',
            '~:data', [],
            '~:included', [],
        ];

        $includes = $request->getQueryParam('include');
        $resource = $this->buildTransactionResource($transaction, $includes);

        $response[2] = $resource['data'];
        foreach ($resource['included'] as $included) {
            $response[4][] = $included;
        }

        return $this->response->withJson($response, 200, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }
}
