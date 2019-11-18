<?php declare(strict_types=1);

namespace App\Controller\AvailabilityExceptions;

use App\Controller\BaseController;
use App\Service\LogService;
use Ramsey\Uuid\Uuid;
use Slim\Http\Request;
use Slim\Http\Response;

class Create extends BaseAvailability
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

        // check listing
        $listing = $this->getListingService()->searchListingByUuid($input_updated['listingId'], $user['uuid']);

        // create the availability_exception for list
        $input_updated['listingId'] = $listing['uuid'];
        $avail = $this->getListingService()->addAvailabilityException($input_updated);

        LogService::dump($avail, "avail");

        // build response
        $response = [
            '^ ',
            '~:data', [],
        ];

        $resource = $this->buildAvailabilityExceptionResource($avail);

        $response[2] = $resource;

        return $this->response->withJson($response, 200, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

    }
}
