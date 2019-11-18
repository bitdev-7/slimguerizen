<?php declare(strict_types=1);

namespace App\Controller;

use App\Service\ListingService;
use App\Service\LogService;
use App\Service\MailService;
use App\Service\UserService;
use Slim\Container;
use Slim\Http\Request;
use Slim\Http\Response;

abstract class BaseController
{
    /**
     * @var Container
     */
    protected $container;

    /**
     * @var Request $request
     */
    protected $request;

    /**
     * @var Response $response
     */
    protected $response;

    protected $baseUrl;

    /**
     * @var array
     */
    protected $args;

    public function __construct(Container $container)
    {
        $this->container = $container;
        $container->get('logger');
    }

    protected function getUserService(): UserService
    {
        return $this->container->get('user_service');
    }

    protected function getMailService(): MailService
    {
        return $this->container->get('mailer');
    }

    protected function getListingService(): ListingService
    {
        return $this->container->get('listing_service');
    }

    protected function setParams(Request $request, Response $response, array $args)
    {
        $this->request = $request;
        $this->response = $response;
        $this->args = $args;
        $this->baseUrl = strstr($request->getUri()->__toString(), $request->getUri()->getPath(), true);
    }

    /**
     * @param string $status
     * @param mixed $message
     * @param int $code
     * @return Response
     */
    protected function jsonResponse(string $status, $message, int $code):  Response
    {
        $result = [
            'code' => $code,
            'status' => $status,
            'message' => $message,
        ];

        return $this->response->withJson($result, $code, JSON_PRETTY_PRINT);
    }

    /**
     * @return array
     */
    protected function getInput()
    {
        return $this->request->getParsedBody();
    }

    protected static function escapeInput($input)
    {
        $inputEscaped = [];
        foreach($input as $key => $value) {
            LogService::log("key=$key, value=".json_encode($value));

            if(!is_numeric($key)) {
                $spl = explode(':', $key);
                // change key
                if(count($spl) > 1) {
                    $key = $spl[1];
                }
            }

            if(is_array($value))
                $inputEscaped[$key] = BaseController::escapeInput($value);
            else {
                if(is_string($value)) {
                    if(strpos($value, '~') === 0) {
                        $value = substr($value, 2);
                    }
                }

                $inputEscaped[$key] = $value;
            };
        }
        return $inputEscaped;
    }

    protected function formatTime($dateTime)
    {
        if(empty($dateTime))
            return "";

        LogService::log($dateTime, "input_time");

        $time = strtotime($dateTime);
        return date('Y-m-d', $time) . "T" . date('H:i:s.000', $time) . "Z";
    }

}
