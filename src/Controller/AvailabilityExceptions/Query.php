<?php declare(strict_types=1);

namespace App\Controller\AvailabilityExceptions;

use App\Controller\BaseController;
use App\Exception\ListingException;
use Ramsey\Uuid\Uuid;
use Slim\Http\Request;
use Slim\Http\Response;

class Query extends BaseAvailability
{
    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $this->setParams($request, $response, $args);

        $input = $this->request->getQueryParams();

        $token = $this->container->get('token');
        $user = $this->getUserService()->getUserByEmail($token['user_id']);

        // check listing
        $list = $this->getListingService()->searchListingByUuid($input['listingId'], $user['uuid']);

        // check listing
        $constraint['start'] = $input['start'];
        $constraint['end'] = $input['end'];
        $constraint['listingId'] = $list['id'];

        $perPage = (int)$this->request->getQueryParam('per_page', 100);
        $page = (int)$this->request->getQueryParam('page', 1);
        if($page <= 0 || $perPage <= 0) {
            throw new ListingException("invalid pagination param", 404);
        }

        $avails = $this->getListingService()->searchAvailabilityException($constraint, $page, $perPage);

        // only for demo
        $resp = [
            "^ ",
            "~:data", [],
            "~:meta", [ "^ ",
                "~:totalItems", count($avails['data']),
                "~:totalPages", $avails['totalPages'],
                "~:page", $page,
                "~:perPage", $perPage,
            ],
        ];

        foreach ($avails['data'] as $avail) {
            $resource = $this->buildAvailabilityExceptionResource($avail);
            $resp[2][] = $resource;
        }

        return $this->response->withJson($resp, 200, JSON_PRETTY_PRINT);
    }
}
