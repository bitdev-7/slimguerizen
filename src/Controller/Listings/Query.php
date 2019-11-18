<?php declare(strict_types=1);

namespace App\Controller\Listings;

use App\Controller\BaseController;
use App\Exception\ListingException;
use Ramsey\Uuid\Uuid;
use Slim\Http\Request;
use Slim\Http\Response;

class Query extends BaseListing
{
    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $this->setParams($request, $response, $args);

        $token = $this->container->get('token');

        $user = $this->getUserService()->getUserByEmail($token['user_id']);

        $perPage = (int)$this->request->getParam('per_page', 100);
        $page = (int)$this->request->getParam('page', 1);
        if($page <= 0 || $perPage <= 0) {
            throw new ListingException("invalid pagination param", 404);
        }

        $listings = $this->getListingService()->queryByUser($user['uuid'], $page, $perPage);

        $resp = [
            "^ ",
            "~:data", [],
            "~:included", [],
            "~:meta", [ "^ ",
                "~:totalItems", count($listings['data']),
                "~:totalPages", $listings['totalPages'],
                "~:page", $page,
                "~:perPage", $perPage,
            ],
        ];

        $includes = $request->getQueryParam('include');

        foreach ($listings['data'] as $listing) {
            $resource = $this->buildListingResource($listing, $includes);
            $resp[2][] = $resource['data'];

            foreach ($resource['included'] as $included) {
                $resp[4][] = $included;
            }
        }

        return $this->response->withJson($resp, 200, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }

    public function queryPublic(Request $request, Response $response, array $args): Response
    {
        $this->setParams($request, $response, $args);

        $perPage = (int)$this->request->getParam('per_page', 100);
        $page = (int)$this->request->getParam('page', 1);
        if($page <= 0 || $perPage <= 0) {
            throw new ListingException("invalid pagination param", 404);
        }

        $filter['authorUuid'] = $this->request->getParam('authorId');
        $filter['origin'] = $this->request->getParam('origin');
        $filter['bounds'] = $this->request->getParam('bounds');
        $filter['price'] = $this->request->getParam('price');

        $listings = $this->getListingService()->queryByFilter($filter, $page, $perPage);

        // only for demo
        $resp = [
            "^ ",
            "~:data", [],
            "~:included", [],
            "~:meta", [ "^ ",
                "~:totalItems", count($listings['data']),
                "~:totalPages", $listings['totalPages'],
                "~:page", $page,
                "~:perPage", $perPage,
            ],
        ];

        $includes = $request->getQueryParam('include');

        foreach ($listings['data'] as $listing) {
            $resource = $this->buildPublicListingResource($listing, $includes);
            $resp[2][] = $resource['data'];

            foreach ($resource['included'] as $included) {
                $resp[4][] = $included;
            }
        }

        return $this->response->withJson($resp, 200, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }


}
