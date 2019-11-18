<?php
/**
 * Created by PhpStorm.
 * User: Home
 * Date: 6/22/2019
 * Time: 12:35 AM
 */

namespace App\Controller\AvailabilityExceptions;


use App\Controller\BaseController;
use App\Service\ListingService;
use App\Service\LogService;
use App\Service\UserService;
use Slim\Container;

class BaseAvailability extends BaseController
{
    protected function buildAvailabilityExceptionResource($avail)
    {
        $data = ["^ ",
            '~:id', '~u' . $avail['uuid'],
            '~:type', '~:availabilityException',
            '~:attributes', ["^ ",
                "~:seats", $avail['seats'],
                "~:start", '~t' . $this->formatTime($avail['start']),
                "~:end", '~t' . $this->formatTime($avail['end']),
             ],
        ];

        return $data;
    }

}