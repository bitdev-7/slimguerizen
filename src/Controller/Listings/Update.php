<?php declare(strict_types=1);

namespace App\Controller\Listings;

use App\Controller\BaseController;
use App\Service\LogService;
use Illuminate\Support\Facades\Log;
use Ramsey\Uuid\Uuid;
use Slim\Http\Request;
use Slim\Http\Response;

class Update extends BaseListing
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

        $listing = $this->getListingService()->searchListingByUuid($input_updated['id'], $user['uuid']);

        if(isset($input_updated['publicData'])) {
            $input_updated['publicData'] = array_merge($listing['publicData'], $input_updated['publicData']);
        }

        LogService::dump($input_updated, "input_updated", "Listings_Update");

        $listing = $this->getListingService()->updateListing($listing['uuid'], $input_updated);

        // update images
        if(isset($input_updated['images'])) {
            $this->getListingService()->updateListingImage($listing['uuid'], $input_updated['images']);
        }

        $response = [
            '^ ',
            '~:data', [],
            '~:included', [],
        ];

        $includes = $request->getQueryParam('include');

        $resource = $this->buildListingResource($listing, $includes);

        $response[2] = $resource['data'];
        foreach ($resource['included'] as $included) {
            $response[4][] = $included;
        }

        return $this->response->withJson($response, 200, JSON_PRETTY_PRINT);
    }
}
