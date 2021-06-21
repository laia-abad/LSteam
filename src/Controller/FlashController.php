<?php
declare(strict_types=1);

namespace SallePW\SlimApp\Controller;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Flash\Messages;
use Slim\Routing\RouteContext;
use Slim\Views\Twig;


final class FlashController
{
    //constructor
    public function __construct(
        private Twig $twig,
        private Messages $flash)
    {
    }

    public function addMessage(Request $request, Response $response): Response
    {
        $this->flash->addMessage(
            'notifications',
            'Flash messages in action!'
        );

        $routeParser = RouteContext::fromRequest($request)->getRouteParser();

        //redirigim al home
        return $response
            ->withHeader('Location', $routeParser->urlFor("home"))
            ->withStatus(302);
    }
}