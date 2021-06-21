<?php
declare(strict_types=1);

namespace SallePW\SlimApp\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Routing\RouteContext;

final class StartSessionMiddleware
{
    public function __invoke(Request $request, RequestHandler $next): Response
    {
        if(session_id() == '' || !isset($_SESSION)) {
            // session isn't started
            session_start();
        }

        return $next->handle($request);
    }
}