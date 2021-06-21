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


final class WishlistController
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

    //mostrem la wishlist
    public function showWishlist(Request $request, Response $response): Response
    {
        $routeParser = RouteContext::fromRequest($request)->getRouteParser();
        //si estem loguejats posem el usuari i la foto de sessio 
        if (isset($_SESSION['id'])) {
            $messages = $this->flash->getMessages();

            $notifications = $messages['notifications'] ?? [];

            $user = $this->userRepository->getUserByToken($_SESSION['id']);
            $credentials = array(
                'username' => $user->getUsername(),
                'picture' => $user->getPicture()
            );

            //retornem els jocs de la wishlist
            $wishlist = $this->userRepository->getWishlistFromUser($user->getUsername());
            //fem crida a la api
            $gamesAPI = $this->apiCall->getGames();
            //declarem variables
            $found = false;
            $gamesFull = [];
            //comprobem si trobem els jocs
            foreach ($wishlist as &$game) {
                foreach ($gamesAPI as &$gameAPI) {
                    if ($gameAPI->gameID == $game['gameId']) {
                        foreach ($gamesFull as &$gameFull) {
                            if ($gameAPI->gameID == $gameFull->gameID) {
                                $found = true;
                            }
                        }
                        if (!$found) {
                            $gamesFull[] = $gameAPI;
                        }
                        $found = false;
                    }
                }
            }

            return $this->twig->render(
                $response,
                'wishlist.twig',
                [
                    'notifications' => $notifications,
                    'wishlist' => $gamesFull, 
                    'credentials' => $credentials,
                ]
            );
        } else {
            //si no estem loguejats redirigim al login
            $this->flash->addMessage(
                'notifications',
                "You must login to access this page",
            );

            return $response
                ->withHeader('Location', $routeParser->urlFor("login"))
                ->withStatus(302);
        }

    }

    public function wishGame(Request $request, Response $response): Response
    {
        $gameId = $request->getAttribute('gameId');

        $user = $this->userRepository->getUserByToken($_SESSION['id']);

        $this->userRepository->wishGame($user->getUsername(), $gameId);

        $routeParser = RouteContext::fromRequest($request)->getRouteParser();

        return $response
                ->withHeader('Location', $routeParser->urlFor("store"))
                ->withStatus(302);
    }

    //mostrem els detalls del joc
    public function showDetails(Request $request, Response $response): Response
    {
        $gameId = $request->getAttribute('gameId');

        //mostrem la informacio del usuari
        $user = $this->userRepository->getUserByToken($_SESSION['id']);
        $credentials = array(
            'username' => $user->getUsername(),
            'picture' => $user->getPicture()
        );

        //fem la crida a la api
        $gamesAPI = $this->apiCall->getGames();
        $gameDetails = null;
        foreach ($gamesAPI as &$gameAPI) {
            if ($gameAPI->gameID == $gameId) {
                $gameDetails = $gameAPI;
            }   
        }

        return $this->twig->render(
            $response,
            'wishlist.twig',
            [
                'gameDetails' => $gameDetails,
                'credentials' => $credentials,
            ]
        );
    }

    //borrem de la wishlist
    public function deleteWish(Request $request, Response $response): Response
    {
        $gameId = $request->getAttribute('gameId');

        $user = $this->userRepository->getUserByToken($_SESSION['id']);

        $routeParser = RouteContext::fromRequest($request)->getRouteParser();

        $this->userRepository->deleteWishGame($user->getUsername(), $gameId);

        return $response
                ->withHeader('Location', $routeParser->urlFor("wishlist"))
                ->withStatus(302);
    }
}
