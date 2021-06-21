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


final class FriendRequestsController
{
    private Twig $twig;
    private UserRepository $userRepository;
    private Messages $flash;

    // You can also use https://stitcher.io/blog/constructor-promotion-in-php-8
    public function __construct(Twig $twig, UserRepository $userRepository, Messages $flash)
    {
        $this->twig = $twig;
        $this->userRepository = $userRepository;
        $this->flash = $flash;
    }

    public function showRequests(Request $request, Response $response): Response
    {

        $routeParser = RouteContext::fromRequest($request)->getRouteParser();
        $messages = $this->flash->getMessages();
        $notifications = $messages['notifications'] ?? [];

        $requestTab = true; //es true si estem a l'apartat de sol.licituds d'amistat

        //si la sessio esta iniciada
        if (isset($_SESSION['id'])) {

            //busquem les dades del user i les passem a un array per a passar-les al twig
            $user = $this->userRepository->getUserByToken($_SESSION['id']);
            $credentials = array(
                'username' => $user->getUsername(),
                'picture' => $user->getPicture()
            );

            //busquem les solicituts d'amistat de l'user
            $requests = $this->userRepository->getFriendRequestsForUser($user->getUsername());

            return $this->twig->render(
                $response,
                'friend-requests.twig',
                [
                    'notifications' => $notifications,
                    'requests' => $requests,
                    'formMethod' => "POST",
                    'credentials' => $credentials,
                    'logout' => $routeParser->urlFor("logout"),
                    'requestTab' => $requestTab
                ]
            );
        } else {
            //si no esta loguejat redirigim al login
            return $response
                ->withHeader('Location', $routeParser->urlFor("login"))
                ->withStatus(302);
        }
    }

    public function acceptFriendRequests(Request $request, Response $response): Response
    {
        $requestId = $request->getAttribute('requestId'); //agafem l'atribut de la url
        $requestTab = true; //es true si estem a l'apartat de sol.licituds d'amistat
        $error = false; //es true si hi ha error

        //busquem les dades del user i les passem a un array per a passar-les al twig
        $user = $this->userRepository->getUserByToken($_SESSION['id']);
        $credentials = array(
            'username' => $user->getUsername(),
            'picture' => $user->getPicture()
        );

        //Comprovem que el request sigui valid, si ho es acceptem la sol.licitud
        if (!$this->userRepository->checkRequestId($user->getUsername(), $requestId)) {
            $error = true;
        } else {
            $this->userRepository->acceptFriendRequest($requestId, date('Y-m-d'));
        }

        //busquem les solicituts d'amistat de l'user
        $requests = $this->userRepository->getFriendRequestsForUser($user->getUsername());

        $routeParser = RouteContext::fromRequest($request)->getRouteParser();

        return $this->twig->render(
            $response,
            'friend-requests.twig',
            [
                'logout' => $routeParser->urlFor("logout"),
                'formMethod' => "POST",
                'credentials' => $credentials,
                'requestTab' => $requestTab,
                'requests' => $requests,
                'error' => $error
            ]
        );
    }

    public function declineFriendRequests(Request $request, Response $response): Response
    {
        $requestId = $request->getAttribute('requestId'); //agafem l'atribut de la url
        $requestTab = true; //es true si estem a l'apartat de sol.licituds d'amistat
        $error = false; //es true si hi ha error

        //busquem les dades del user i les passem a un array per a passar-les al twig
        $user = $this->userRepository->getUserByToken($_SESSION['id']);
        $credentials = array(
            'username' => $user->getUsername(),
            'picture' => $user->getPicture()
        );

        //Comprovem que el request sigui valid, si ho es rebutgem la sol.licitud
        if (!$this->userRepository->checkRequestId($user->getUsername(), $requestId)) {
            $error = true;
        } else {
            $this->userRepository->declineFriendRequest($requestId);
        }

        //busquem les solicituts d'amistat de l'user
        $requests = $this->userRepository->getFriendRequestsForUser($user->getUsername());

        $routeParser = RouteContext::fromRequest($request)->getRouteParser();

        return $this->twig->render(
            $response,
            'friend-requests.twig',
            [
                'logout' => $routeParser->urlFor("logout"),
                'formMethod' => "POST",
                'credentials' => $credentials,
                'requestTab' => $requestTab,
                'requests' => $requests,
                'error' => $error
            ]
        );
    }

    public function showForm(Request $request, Response $response): Response
    {

        $routeParser = RouteContext::fromRequest($request)->getRouteParser();

        //si la sessio esta iniciada
        if (isset($_SESSION['id'])) {
            //busquem les dades del user i les passem a un array per a passar-les al twig
            $user = $this->userRepository->getUserByToken($_SESSION['id']);
            $credentials = array(
                'username' => $user->getUsername(),
                'picture' => $user->getPicture()
            );

            return $this->twig->render(
                $response,
                'friend-requests.twig',
                [
                    'formAction' => $routeParser->urlFor("send-request"),
                    'logout' => $routeParser->urlFor("logout"),
                    'formMethod' => "POST",
                    'credentials' => $credentials
                ]
            );
        } else {
            //si no esta loguejat redirigim al login
            return $response
                ->withHeader('Location', $routeParser->urlFor("login"))
                ->withStatus(302);
        }
    }

    public function sendFriendRequests(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody(); //conte la informacio introduida pel usuari
        $successful = false; //es true si s'ha enviat correctament

        $errors = []; //array de possibles errors

        //busquem les dades del user i les passem a un array per a passar-les al twig
        $user = $this->userRepository->getUserByToken($_SESSION['id']);
        $credentials = array(
            'username' => $user->getUsername(),
            'picture' => $user->getPicture()
        );

        //Comprovem que el user al que s'esta enviant la sol.licitud existeixi
        if (!$this->userRepository->checkUserExists($data['username'])) {
            $errors['username'] = "User doesn't exist";
        }

        //Comprovem que no siguin amics ja
        $friends = $this->userRepository->getFriendsFromUser($user->getUsername());
        foreach ($friends as $friend) {
            if ($data['username'] == $friend->getUsername()) {
                $errors['username'] = "You are already friends with this user";
            }
        }

        //Comprovem que no li hagi enviat ja una sol.licitud
        $friends = $this->userRepository->getFriendRequestsFromUser($user->getUsername());
        foreach ($friends as $friend) {
            if ($data['username'] == $friend->getUsername()) {
                $errors['username'] = "You already sent a friend request to this user";
            }
        }

        //si no hi ha errors, la enviem
        if (empty($errors)) {
            $this->userRepository->sendFriendRequest($user->getUsername(), $data['username'], uniqid());
            $successful = true;
        }

        $routeParser = RouteContext::fromRequest($request)->getRouteParser();

        return $this->twig->render(
            $response,
            'friend-requests.twig',
            [
                'formErrors' => $errors,
                'formData' => $data,
                'formAction' => $routeParser->urlFor("send-request"),
                'logout' => $routeParser->urlFor("logout"),
                'formMethod' => "POST",
                'credentials' => $credentials,
                'successful' => $successful
            ]
        );
    }
}
