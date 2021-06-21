<?php

declare(strict_types=1);

namespace SallePW\SlimApp\Controller;


use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Flash\Messages;
use Slim\Views\Twig;
use SallePW\SlimApp\Model\UserRepository;
use SallePW\SlimApp\Model\User;
use Slim\Routing\RouteContext;
use DateTime;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

final class WalletController
{
    private Twig $twig;
    private UserRepository $userRepository;

    private Messages $flash;

    // You can also use https://stitcher.io/blog/constructor-promotion-in-php-8
    //constructor
    public function __construct(Twig $twig, UserRepository $userRepository, Messages $flash)
    {
        $this->twig = $twig;
        $this->userRepository = $userRepository;
        $this->flash = $flash;
    }

    //mostrem wl wallet
    public function showForm(Request $request, Response $response): Response
    {

        $routeParser = RouteContext::fromRequest($request)->getRouteParser();
        //si estem loguejats posem el usuari, la foto de sessio i els diners actuals
        if (isset($_SESSION['id'])) {
            $user = $this->userRepository->getUserByToken($_SESSION['id']);
            $credentials = array(
                'username' => $user->getUsername(),
                'wallet' => $user->getWallet(),
                'picture' => $user->getPicture()
            );
            
            return $this->twig->render(
                $response,
                'wallet.twig',
                [
                    'formAction' => $routeParser->urlFor("handle-form-wallet"),
                    'logout' => $routeParser->urlFor("logout"),
                    'formMethod' => "POST",
                    'credentials' => $credentials
                ]
            );
        } else {
            //si no estem loguejats redirijim al login
            $this->flash->addMessage(
                'notifications',
                "You must login to access this page",
            );

            return $response
                ->withHeader('Location', $routeParser->urlFor("login"))
                ->withStatus(302);
        }
    }

    public function handleFormSubmission(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $errors = [];

        $user = $this->userRepository->getUserByToken($_SESSION['id']);

        //mostrem un error si no anyadim diners
        if ($data['amount'] < 0) {
            $errors['amount'] = 'The amount should be greater than 0.';
        } else {
            //afegim dinars a la bade de dades i cambiem la quantitat que mostrem
            $user->setWallet($data['amount'] + $user->getWallet());
            $this->userRepository->updateMoney($user->getUsername(), $user->getWallet());
        }
        
        //mostrem la informacio del usuari
        $credentials = array(
            'username' => $user->getUsername(),
            'wallet' => $user->getWallet(),
            'picture' => $user->getPicture()
        );

        $routeParser = RouteContext::fromRequest($request)->getRouteParser();

        return $this->twig->render(
            $response,
            'wallet.twig',
            [
                'formErrors' => $errors,
                'formData' => $data,
                'formAction' => $routeParser->urlFor("handle-form-wallet"),
                'logout' => $routeParser->urlFor("logout"),
                'formMethod' => "POST",
                'credentials' => $credentials
            ]
        );
    }
}
