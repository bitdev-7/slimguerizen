<?php declare(strict_types=1);

namespace App\Controller\Transactions;

use App\Controller\BaseController;
use Ramsey\Uuid\Uuid;
use Slim\Http\Request;
use Slim\Http\Response;

class Query extends BaseTransaction
{

    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $this->setParams($request, $response, $args);

        $perPage = (int)$this->request->getParam('per_page', 100);
        $page = (int)$this->request->getParam('page', 1);
        if($page <= 0 || $perPage <= 0) {
            throw new ListingException("invalid pagination param", 404);
        }

        $token = $this->container->get('token');
        $user = $this->getUserService()->getUserByEmail($token['user_id']);

        $only = $this->request->getParam('only', 'sale');
        if($only == 'sale') {
            $filter['providerId'] = $user['uuid'];
        } else {
            $filter['customerId'] = $user['uuid'];
        }

        $transitions = $this->getListingService()->searchTransaction($filter, $page, $perPage);

        // only for demo
        $resp = [
            "^ ",
            "~:data", [],
            "~:included", [],
            "~:meta", [ "^ ",
                "~:totalItems", count($transitions['data']),
                "~:totalPages", $transitions['totalPages'],
                "~:page", $page,
                "~:perPage", $perPage,
            ],
        ];

        $includes = $request->getQueryParam('include');

        foreach ($transitions['data'] as $transaction) {
            $resource = $this->buildTransactionResource($transaction, $includes);
            $resp[2][] = $resource['data'];

            foreach ($resource['included'] as $included) {
                $resp[4][] = $included;
            }
        }

        return $this->response->withJson($resp, 200, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }
}
