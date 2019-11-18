<?php declare(strict_types=1);

namespace App\Controller\AvailabilityExceptions;

use App\Controller\BaseController;
use App\Service\LogService;
use Ramsey\Uuid\Uuid;
use Slim\Http\Request;
use Slim\Http\Response;

class Delete extends BaseAvailability
{
    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $this->setParams($request, $response, $args);

        $input = $this->getInput();
        // escape prefix
        $input_updated = $this->escapeInput($input);

        LogService::dump($input_updated, "input_updated");

        // get availability_exception
        $avail = $this->getListingService()->searchAvailabilityExceptionByUuid($input_updated['id']);

        // check owner
        $token = $this->container->get('token');
        $user = $this->getUserService()->getUserByEmail($token['user_id']);

        // check listing
        $this->getListingService()->searchListing($avail['listingId'], $user['uuid']);

        // delete the availability_exception for list
        $this->getListingService()->deleteAvailabilityException($avail['uuid']);

        // build response
        $response = [
            '^ ',
            '~:data', ["^ ",
                "~:id", "~u" . $input_updated['id'],
                "~:type", "~:availabilityException",
            ],
        ];

        return $this->response->withJson($response, 200, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }
}
