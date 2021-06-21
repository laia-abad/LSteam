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

final class LoginController
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

    //mostrem el login
    public function showForm(Request $request, Response $response): Response
    {
        $messages = $this->flash->getMessages();

        $notifications = $messages['notifications'] ?? [];

        $routeParser = RouteContext::fromRequest($request)->getRouteParser();

        return $this->twig->render(
            $response,
            'login.twig',
            [
                'formAction' => $routeParser->urlFor("handle-form-login"),
                'formMethod' => "POST",
                'notifications' => $notifications
            ]
        );
    }

    public function handleFormSubmission(Request $request, Response $response): Response
    {   
        //agafem la informacio
        $data = $request->getParsedBody();
        //declarem la variable errors
        $errors = [];
        //contrastem amb la base de dades la informacio que ha introduit el usuari
        $user = $this->userRepository->loginUser($data['info'], hash('ripemd160', $data['password']));

        $isnotempty = (array)$user;
        //tornem a mostrar el login si hi ha error 
        if (!$isnotempty) {
            $errors['generic'] = 'The credentials are incorrect';
            $routeParser = RouteContext::fromRequest($request)->getRouteParser();

            return $this->twig->render(
                $response,
                'login.twig',
                [
                    'formErrors' => $errors,
                    'formData' => $data,
                    'formAction' => $routeParser->urlFor("handle-form-login"),
                    'formMethod' => "POST"
                ]
            );
        } else {
            //si tot esta correcte pasem a la store

            //fem que la id sigui el token del usuari
            $_SESSION["id"] = $user->getToken();

            $routeParser = RouteContext::fromRequest($request)->getRouteParser();

            return $response
                ->withHeader('Location', $routeParser->urlFor("store"));
        }
    }

    //redirigim que despres de fer logout torni al login
    public function logout(Request $request, Response $response): Response
    {   
        //tanquem la sessio
        session_destroy();

        $routeParser = RouteContext::fromRequest($request)->getRouteParser();

        return $response
            ->withHeader('Location', $routeParser->urlFor("login"));
    }

    
}
