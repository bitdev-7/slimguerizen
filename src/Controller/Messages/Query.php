<?php declare(strict_types=1);

namespace App\Controller\Messages;

use App\Controller\BaseController;
use Ramsey\Uuid\Uuid;
use Slim\Http\Request;
use Slim\Http\Response;

class Query extends BaseMessage
{
    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $this->setParams($request, $response, $args);

        $input = $this->request->getQueryParams();

//        $token = $this->container->get('token');
//        $user = $this->getUserService()->getUserByEmail($token['user_id']);
//
//        // check listing
//        $list = $this->getListingService()->searchListingByUuid($input['listingId'], $user['id']);

        $constraint['transaction_id'] = $input['transaction_id'];

        $perPage = (int)$this->request->getQueryParam('per_page', 100);
        $page = (int)$this->request->getQueryParam('page', 1);
        if($page <= 0 || $perPage <= 0) {
            throw new ListingException("invalid pagination param", 404);
        }

        $msgs = $this->getListingService()->searchMessage($constraint, $page, $perPage);

        // only for demo
        $resp = [
            "^ ",
            "~:data", [],
            "~:included", [],
            "~:meta", [ "^ ",
                "~:totalItems", count($msgs['data']),
                "~:totalPages", $msgs['totalPages'],
                "~:page", $page,
                "~:perPage", $perPage,
            ],
        ];

        $includes = $request->getQueryParam('include');

        foreach ($msgs['data'] as $msg) {
            $resource = $this->buildMessageResource($msg, $includes);
            $resp[2][] = $resource['data'];

            foreach ($resource['included'] as $included) {
                $resp[4][] = $included;
            }
        }

        return $this->response->withJson($resp, 200, JSON_PRETTY_PRINT);
    }
}
