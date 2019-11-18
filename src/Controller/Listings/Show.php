<?php declare(strict_types=1);

namespace App\Controller\Listings;

use App\Controller\BaseController;
use App\Service\LogService;
use Ramsey\Uuid\Uuid;
use Slim\Http\Request;
use Slim\Http\Response;

class Show extends BaseListing
{
    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $this->setParams($request, $response, $args);

        $token = $this->container->get('token');
        $input = $this->request->getParams();

        $user = $this->getUserService()->getUserByEmail($token['user_id']);

        $list = $this->getListingService()->searchListingByUuid($input['id'], $user['uuid']);

        // build response
        $response = [
            '^ ',
            '~:data', [],
            '~:included', [],
        ];

        $includes = $request->getQueryParam('include');
        $resource = $this->buildListingResource($list, $includes);

        $response[2] = $resource['data'];
        foreach ($resource['included'] as $included) {
            $response[4][] = $included;
        }

        return $this->response->withJson($response, 200, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }


    public function showPublic(Request $request, Response $response, array $args): Response
    {
        $this->setParams($request, $response, $args);

        $input = $this->request->getParams();

        LogService::dump($input, "input", "showPublic");

        $list = $this->getListingService()->searchListingByUuid($input['id']);

        LogService::dump($list, "list", "showPublic");

        // build response
        $response = [
            '^ ',
            '~:data', [],
            '~:included', [],
        ];

        $includes = $request->getQueryParam('include');
        $resource = $this->buildPublicListingResource($list, $includes);

        LogService::dump($resource, "resource", "showPublic");
        LogService::dump($includes, "includes", "showPublic");

        $response[2] = $resource['data'];
        foreach ($resource['included'] as $included) {
            $response[4][] = $included;
        }

        return $this->response->withJson($response, 200, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }
}
