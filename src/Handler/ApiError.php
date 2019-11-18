<?php declare(strict_types=1);

namespace App\Handler;

use App\Service\LogService;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Ramsey\Uuid\Uuid;

final class ApiError extends \Slim\Handlers\Error
{
    public function __invoke(Request $request, Response $response, \Exception $exception)
    {
        $statusCode = $exception->getCode() <= 599 ? $exception->getCode() : 500;
        $className = new \ReflectionClass(get_class($exception));

        $resp = [
            'errors' => [
                [
                    'id' => Uuid::uuid4(),
                    'status' => $statusCode,
                    'title' => $className->getShortName(),
                    'details' => $exception->getMessage(),
                ],
            ],
        ];
        $body = json_encode($resp, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        LogService::log("error handler: body=" . $body);

        //
        return $response->withStatus($statusCode)->withHeader("Content-type", "application/json")->write($body);
    }
}
