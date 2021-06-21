<?php
declare(strict_types=1);

namespace SallePW\SlimApp\Controller;


use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Flash\Messages;
use Slim\Views\Twig;
use Slim\Routing\RouteContext;
use SallePW\SlimApp\Model\UserRepository;

final class HomeController
{
    private Twig $twig;
    private UserRepository $userRepository;
    private Messages $flash;

    // You can also use https://stitcher.io/blog/constructor-promotion-in-php-8
    //contructor
    public function __construct(Twig $twig, UserRepository $userRepository, Messages $flash)
    {
        $this->twig = $twig;
        $this->userRepository = $userRepository;
        $this->flash = $flash;
    }

    //mostrem el inici
    public function apply(Request $request, Response $response)
    {
        $messages = $this->flash->getMessages();

        $routeParser = RouteContext::fromRequest($request)->getRouteParser();

        $notifications = $messages['notifications'] ?? [];
        //vista si la sessio esta iniciada
        if (isset($_SESSION['id'])) {
            $user = $this->userRepository->getUserByToken($_SESSION['id']);
            $credentials = array(
                'username' => $user->getUsername(),
                'picture' => $user->getPicture()
            );
        } else {
            //vista sense sessio iniciada
            $credentials = null;
        }

        return $this->twig->render(
            $response,
            'home.twig',
            [
                'notifications' => $notifications,
                'credentials' => $credentials,
                'logout' => $routeParser->urlFor("logout"),
                'formMethod' => "POST"
            ]
        );
    }
}