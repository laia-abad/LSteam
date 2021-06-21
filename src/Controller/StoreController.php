<?php

declare(strict_types=1);

namespace SallePW\SlimApp\Controller;


use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Flash\Messages;
use Slim\Views\Twig;
use SallePW\SlimApp\Model\UserRepository;
use SallePW\SlimApp\Model\APICall;
use Slim\Routing\RouteContext;
use SallePW\SlimApp\Model\Repository\GamesAPI;
use SallePW\SlimApp\Model\CacheRepository;
use SallePW\SlimApp\Model\CacheGames;
use SallePW\SlimApp\Model\DecoratorGamesAPI;



final class StoreController
{
    private Twig $twig;
    private UserRepository $userRepository;
    private APICall $apiCall;
    private Messages $flash;
    private CacheRepository $cacheRepository;

    // You can also use https://stitcher.io/blog/constructor-promotion-in-php-8
    //contructor
    public function __construct(Twig $twig, UserRepository $userRepository, Messages $flash,CacheRepository $cacheRepository)
    {
        $this->twig = $twig;
        $this->userRepository = $userRepository;
        $this->apiCall = new APICall();
        $this->flash = $flash;
        $this->cacheRepository = $cacheRepository;
    }

    //mostrem el store
    public function showForm(Request $request, Response $response): Response
    {

        $routeParser = RouteContext::fromRequest($request)->getRouteParser();
        //si estem loguejats posem el usuari i la foto de sessio
        if (isset($_SESSION['id'])) {
            $user = $this->userRepository->getUserByToken($_SESSION['id']);
            $credentials = array(
                'username' => $user->getUsername(),
                'picture' => $user->getPicture()
            );
        } else {
            //sino no ho posem
            $credentials = null;
        }

        //$games = $this->cacheRepository->GetDeals();
        //fem la crida a la api que ens retorna la informacio dels jocs
        $games = $this->apiCall->getGames();

        return $this->twig->render(
            $response,
            'store.twig',
            [
                'logout' => $routeParser->urlFor("logout"),
                'formMethod' => "POST",
                'credentials' => $credentials,
                'games' => $games
            ]
        );
    }

    //opcio de comprar jocs
    public function buyGame(Request $request, Response $response): Response
    {
        $gameId = $request->getAttribute('gameId');

        $routeParser = RouteContext::fromRequest($request)->getRouteParser();

        //si estem logeggats posem el usuari i la foto de sessio
        if (isset($_SESSION['id'])) {
            $user = $this->userRepository->getUserByToken($_SESSION['id']);
            $credentials = array(
                'username' => $user->getUsername(),
                'picture' => $user->getPicture()
            );
        } else {
            //sino no ho posem
            $credentials = null;
        }

        //fem la crida a la api que ens retorna la informacio dels jocs
        $games = $this->apiCall->getGames();
        //retornem els jocs que ha comprat l'usuari
        $userGames = $this->userRepository->getGamesFromUser($user->getUsername());
        $error = false;

        //mirem si tenim diners al wallet per comprar el joc
        foreach ($games as &$game) {
            if ($game->gameID == $gameId) {
                if ($game->normalPrice > $user->getWallet()) {
                    $this->flash->addMessage(
                        'notifications',
                        "You don't have enough money in your wallet.",
                    );
                    $error = true;
                }
            }
        }
        
        //comprobem que no compri un joc que ja ha comprat
        foreach ($userGames as &$Usergame) {
            if ($gameId == $Usergame['gameId']) {
                $this->flash->addMessage(
                    'notifications',
                    "You already bought this game.",
                );
                $error = true;
            }
        }

        //si no hi ha error communiquem que s'ha comprat el joc
        if (!$error) {
            
            $this->flash->addMessage(
                'notifications',
                "You have successfully bought the game!",
            );
            //guardem el joc a la taula
            $this->userRepository->saveGame($user->getUsername(), (int)$gameId);
            //modifiquem els diner que te el usuari
            $this->userRepository->updateMoney($user->getUsername(), $user->getWallet() - $game->normalPrice);
        }

        $routeParser = RouteContext::fromRequest($request)->getRouteParser();
        $messages = $this->flash->getMessages();

        $notifications = $messages['notifications'] ?? [];

        return $this->twig->render(
            $response,
            'store.twig',
            [
                'logout' => $routeParser->urlFor("logout"),
                'formMethod' => "POST",
                'credentials' => $credentials,
                'games' => $games,
                'notifications' => $notifications
            ]
        );
    }


    public function myGames(Request $request, Response $response): Response
    {
        $routeParser = RouteContext::fromRequest($request)->getRouteParser();
        $myGames = true;

        //si estem logeggats posem el usuari i la foto de sessio
        if (isset($_SESSION['id'])) {
            $user = $this->userRepository->getUserByToken($_SESSION['id']);
            $credentials = array(
                'username' => $user->getUsername(),
                'picture' => $user->getPicture()
            );

            //fem la crida a la api
            $gamesAPI = $this->apiCall->getGames();

            //declarem variables
            $found = false;
            $gamesFull = [];

            //agafem el jocs del usuari
            $games = $this->userRepository->getGamesFromUser($user->getUsername());
           
            //comprobem si tenim el joc
            foreach ($games as &$game) {
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
                'store.twig',
                [
                    'credentials' => $credentials,
                    'myGames' => $myGames,
                    'games' => $gamesFull,
                    'logout' => $routeParser->urlFor("logout"),
                ]
            );
        } else {
            
            //l'usuari no ha fet login i redirigim al usuari
            $this->flash->addMessage(
                'notifications',
                "You must login to access this page",
            );

            return $response
                ->withHeader('Location', $routeParser->urlFor("login"))
                ->withStatus(302);
        }
    }
}
