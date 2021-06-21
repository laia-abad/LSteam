<?php

declare(strict_types=1);

namespace SallePW\SlimApp\Controller;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Flash\Messages;
use Slim\Views\Twig;
use SallePW\SlimApp\Model\UserRepository;
use SallePW\SlimApp\Model\User;
use SallePW\SlimApp\Model\APICall;
use Slim\Routing\RouteContext;
use DateTime;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;


final class FriendListController
{
    private Twig $twig;
    private UserRepository $userRepository;
    private APICall $apiCall;
    private Messages $flash;

    // You can also use https://stitcher.io/blog/constructor-promotion-in-php-8
    //constructor
    public function __construct(Twig $twig, UserRepository $userRepository, Messages $flash)
    {
        $this->twig = $twig;
        $this->userRepository = $userRepository;
        $this->apiCall = new APICall();
        $this->flash = $flash;
    }

    //mostrem la freind list
    public function showFriendList(Request $request, Response $response): Response
    {

        $routeParser = RouteContext::fromRequest($request)->getRouteParser();
        $messages = $this->flash->getMessages();

        $notifications = $messages['notifications'] ?? [];
        //si la sessio esta iniciada
        if (isset($_SESSION['id'])) {

            //busquem les dades del user i les passem a un array per a passar-les al twig
            $user = $this->userRepository->getUserByToken($_SESSION['id']);
            $credentials = array(
                'username' => $user->getUsername(),
                'picture' => $user->getPicture()
            );

            //busquem els amics del usuari loguejat i les guardem per enviar-les al twig
            $friends = $this->userRepository->getFriendsFromUser($user->getUsername());
            $showFriends = [];
            foreach ($friends as $friend) {
                $showFriends[]["name"] = $friend->getUsername();
                $showFriends[array_key_last($showFriends)]["accept_date"] = $friend->getAcceptDate();
            }

            return $this->twig->render(
                $response,
                'friend-list.twig',
                [
                    'notifications' => $notifications,
                    'friends' => $showFriends,
                    'credentials' => $credentials,
                    'logout' => $routeParser->urlFor("logout"),
                ]
            );
        } else {
            //si no esta loguejat redirigim al login
            return $response
                ->withHeader('Location', $routeParser->urlFor("login"))
                ->withStatus(302);
        }
    }
    
}