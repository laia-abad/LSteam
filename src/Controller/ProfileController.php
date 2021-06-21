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
use SallePW\SlimApp\Model\Validations;

use Psr\Http\Message\UploadedFileInterface;
use Ramsey\Uuid\Uuid;
use Twig\Node\Expression\Binary\EqualBinary;

final class ProfileController
{
    private Twig $twig;
    private UserRepository $userRepository;
    private Messages $flash;
    private Validations $validations;

    private const UPLOADS_DIR = __DIR__ . '/../../public/assets/uploads/';

    // You can also use https://stitcher.io/blog/constructor-promotion-in-php-8
    //constructor
    public function __construct(Twig $twig, UserRepository $userRepository, Messages $flash)
    {
        $this->twig = $twig;
        $this->userRepository = $userRepository;
        $this->validations = new Validations();
        $this->flash = $flash;
    }

    //mostrem el profile
    public function showForm(Request $request, Response $response): Response
    {

        $routeParser = RouteContext::fromRequest($request)->getRouteParser();

        //quan la sessio esta iniciada
        if (isset($_SESSION['id'])) {
            $user = $this->userRepository->getUserByToken($_SESSION['id']);

            $credentials = array(
                'username' => $user->getUsername(),
                'email' => $user->getEmail(),
                'birthday' => $user->getBirthday()->format('Y-m-d'),
                'phone' => $user->getPhone(),
                'picture' => $user->getPicture(),
            );

            return $this->twig->render(
                $response,
                'profile.twig',
                [
                    'formAction' => $routeParser->urlFor("handle-form-profile"),
                    'logout' => $routeParser->urlFor("logout"),
                    'formMethod' => "POST",
                    'credentials' => $credentials
                ]
            );
        } else {
            //si la sessio no esta iniciada redirigim al login
            $this->flash->addMessage(
                'notifications',
                "You must login to access this page",
            );

            return $response
                ->withHeader('Location', $routeParser->urlFor("login"))
                ->withStatus(302);
        }
    }

    //posem la informacio del usuari
    public function handleFormSubmission(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();

        $uploadedFiles = $request->getUploadedFiles();
        $uploadedFile = $uploadedFiles['picture'];

        $errors = [];

        $user = $this->userRepository->getUserByToken($_SESSION['id']);

        //permetem cambiar el telefon
        if (!$this->validations->validatePhone ($errors, $data['phone'])) {
            $user->setPhone($data['phone']);
            $this->userRepository->updatePhone($user->getUsername(), $user->getPhone());
        }

        //mostrem errors al añadir informacio incorrecte
        if (!empty($uploadedFile->getClientFilename())) {
            if ($uploadedFile->getError() !== UPLOAD_ERR_OK) {
                $errors['picture'] = "An unexpected error occurred uploading the file";
            } else {
                $name = $uploadedFile->getClientFilename();
                
                $fileInfo = pathinfo($name);
                $format = $fileInfo['extension'];

                if (strtolower($format) != 'jpg' && strtolower($format) != 'png') {
                    $errors['picture'] = "The received file extension is not valid (must be .png or .jpg)";
                } else if ($uploadedFile->getSize() >= 1048576) {
                    $errors['picture'] = "The file is too large (max. 1MB)";
                } else if (getimagesize($_FILES['picture']['tmp_name'])[0] > 500 || getimagesize($_FILES['picture']['tmp_name'])[1] > 500) {
                    $errors['picture'] = "The dimensions of the image are too large (max. 500x500)";
                } else{
                    $name = Uuid::uuid4()->toString() . '.' . $fileInfo['extension'];
                    $user->setPicture($name);
                    $uploadedFile->moveTo(self::UPLOADS_DIR . DIRECTORY_SEPARATOR . $name);
                    $this->userRepository->updatePicture($user->getUsername(), $user->getPicture());
                }
            }
        }


        $routeParser = RouteContext::fromRequest($request)->getRouteParser();

        $credentials = array(
            'username' => $user->getUsername(),
            'email' => $user->getEmail(),
            'birthday' => $user->getBirthday()->format('Y-m-d'),
            'phone' => $user->getPhone(),
            'picture' => $user->getPicture(),
        );

        return $this->twig->render(
            $response,
            'profile.twig',
            [
                'formErrors' => $errors,
                'formData' => $data,
                'formAction' => $routeParser->urlFor("handle-form-profile"),
                'logout' => $routeParser->urlFor("logout"),
                'formMethod' => "POST",
                'credentials' => $credentials
            ]
        );
    }

    //formulari per cambiar la contrasenya
    public function changePasswordForm(Request $request, Response $response): Response
    {

        $routeParser = RouteContext::fromRequest($request)->getRouteParser();
        //declarem variables
        $password = true;
        $errors = [];
        //obtenim la informacio
        $data = $request->getParsedBody();
        
        $user = $this->userRepository->getUserByToken($_SESSION['id']);

        $credentials = array(
            'username' => $user->getUsername(),
            'email' => $user->getEmail(),
            'birthday' => $user->getBirthday()->format('Y-m-d'),
            'phone' => $user->getPhone(),
            'picture' => $user->getPicture(),
        );

        return $this->twig->render(
            $response,
            'profile.twig',
            [
                'formErrors' => $errors,
                'formData' => $data,
                'formAction' => $routeParser->urlFor("handle-form-changePassword"),
                'logout' => $routeParser->urlFor("logout"),
                'formMethod' => "POST",
                'credentials' => $credentials,
                'password' => $password
            ]
        );
    }




    public function changePassword(Request $request, Response $response): Response
    {     
        $data = $request->getParsedBody();

        $errors = [];
        $success = '';

        $user = $this->userRepository->getUserByToken($_SESSION['id']);
        $credentials = array(
            'username' => $user->getUsername(),
            'email' => $user->getEmail(),
            'birthday' => $user->getBirthday()->format('Y-m-d'),
            'phone' => $user->getPhone(),
            'picture' => $user->getPicture(),
        );

        $password = true;
        //validem la contrasenya introduida
        if (strcmp(hash('ripemd160', $data['old_password']), $user->getPassword()) != 0 || $this->validations->validatePassword ($errors, $data['password'], $data['passwordRepeat'])) {
            $errors['passwordGeneric'] = "You introduced something wrong.";
        }

        $routeParser = RouteContext::fromRequest($request)->getRouteParser();

        return $this->twig->render(
            $response,
            'profile.twig',
            [
                'formErrors' => $errors,
                'formData' => $data,
                'formAction' => $routeParser->urlFor("handle-form-changePassword"),
                'logout' => $routeParser->urlFor("logout"),
                'formMethod' => "POST",
                'credentials' => $credentials,
                'password' => $password,
                'success' => $success
            ]
        );
    }
}
