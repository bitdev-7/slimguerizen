<?php declare(strict_types=1);

namespace App\Controller\Listings;

use App\Controller\BaseController;
use App\Service\LogService;
use Ramsey\Uuid\Uuid;
use Slim\Http\Request;
use Slim\Http\Response;

class Close extends BaseListing
{
    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $this->setParams($request, $response, $args);

        $token = $this->container->get('token');

        $input = $this->getInput();
        $input_updated = $this->escapeInput($input);

        LogService::dump($input_updated, "input_updated");

        $user = $this->getUserService()->getUserByEmail($token['user_id']);

        $list = $this->getListingService()->searchListingByUuid($input_updated['id'], $user['uuid']);

        $list = $this->getListingService()->updateListingState($list['uuid'], "closed");

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

        return $this->response->withJson($response, 200, JSON_PRETTY_PRINT);
    }
}
