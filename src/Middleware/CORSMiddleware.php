<?php declare(strict_types=1);

namespace App\Middleware;

use App\Exception\AuthException;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Request;
use Slim\Http\Response;
use \Firebase\JWT\JWT;

class CORSMiddleware
{
    /**
     * @param Request $request
     * @param Response $response
     * @param Callable $next
     * @return ResponseInterface
     * @throws AuthException
     */
    public function __invoke(Request $request, Response $response, $next): ResponseInterface
    {
        $response = $next($request, $response);
        $response = $response->withHeader('Access-Control-Allow-Origin', '*');
        return $response;
    }

}
