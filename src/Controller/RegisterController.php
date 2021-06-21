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

final class RegisterController
{
    private Twig $twig;
    private UserRepository $userRepository;
    private Messages $flash;
    private Validations $validations;

    // You can also use https://stitcher.io/blog/constructor-promotion-in-php-8
    //contructor
    public function __construct(Twig $twig, UserRepository $userRepository, Messages $flash)
    {
        $this->twig = $twig;
        $this->userRepository = $userRepository;
        $this->validations = new Validations();
        $this->flash = $flash;
    }

    //mostrem el regsitre
    public function showForm(Request $request, Response $response): Response
    {

        $routeParser = RouteContext::fromRequest($request)->getRouteParser();

        return $this->twig->render(
            $response,
            'register.twig',
            [
                'formAction' => $routeParser->urlFor("handle-form-register"),
                'formMethod' => "POST"
            ]
        );
    }

    public function handleFormSubmission(Request $request, Response $response): Response
    {   
        //agafem la informacio
        $data = $request->getParsedBody();
        //declarem variables
        $error = false; 
        $errors = [];
        $emailSent = false;
        $finished = false;

        //comprobem errors del email
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'The email address is not valid';
    
        } else {
            //23
            $url = substr($data['email'], -14);
            if (strcmp($url, "@salle.url.edu") != 0) {
                $errors['email'] = "Only emails from the domain @salle.url.edu are accepted.";
          
            } elseif ($this->userRepository->inUse($data['email'])) {
                $errors['email'] = "The email is already in use.";
            
            }
        }

        //comprobem errors del username
        if (!ctype_alnum($data['username'])) {
            $errors['username'] = "The username must be alphanumeric.";
            
        } else if ($this->userRepository->inUse($data['username'])) {
            $errors['username'] = "The username is already in use.";
           
        }

        //fem la validacio de la contrasenya
        $this->validations->validatePassword ($errors, $data['password'], $data['passwordRepeat']);

        //comprobem errors de la data de naixement
        if (str_contains($data['birthday'], "-")) {
            $date = explode('-', $data['birthday']);
            if (checkdate((int)$date[1], (int)$date[2], (int)$date[0]) && !empty($date[2]) && strlen($date[0]) == 4 && strlen($date[1]) == 2 && strlen($date[2]) == 2) {
                if (strtotime((int)$date[0] . "-" . (int)$date[1] . "-" . (int)$date[2]) > strtotime('-18 years')) {
                    $errors['birthday'] = "Only users of legal age (more than 18 years) can be registered.";
                    $error = true;
                } elseif (strtotime((int)$date[0] . "-" . (int)$date[1] . "-" . (int)$date[2]) < strtotime('-120 years')) {
                    $errors['birthday'] = "No one that old is alive.";
                    $error = true;
                } else {
                    $date = $data['birthday'];
                }
            } else {
                $errors['birthday'] = "The date format is incorrect";
                
                $error = true;
            }
        } else {
            $errors['birthday'] = "The date format is incorrect";
            $error = true;
        }

        //validem el telefon
        $this->validations->validatePhone ($errors, $data['phone']);

        //si no hi ha errors guardem a la base de dades
        if (empty($errors)) {
            
            $user = User::withParameters(
                $data['username'],
                $data['email'],
                hash('ripemd160', $data['password']),
                DateTime::createFromFormat('Y-m-d', $date[0].'-'.$date[1].'-'.$date[2]),
                $data['phone'],
                uniqid()
            );
       
            //guardem a la base de dades
            $this->userRepository->saveUser($user);
            $user = $this->userRepository->getLastUser();
            $finished = true;
            $mail = new PHPMailer(true);
            try {
                //Server settings
                //$mail->SMTPDebug = SMTP::DEBUG_SERVER;                //Enable verbose debug output
                $mail->isSMTP();                                      //Send using SMTP
                $mail->Host       = 'mail.smtpbucket.com';            //Set the SMTP server to send through
                $mail->Port       = 8025;                              //TCP port to connect to, use 465 for `PHPMailer::ENCRYPTION_SMTPS` above

                //Recipients
                $mail->setFrom('no-reply@lsteam.com', 'LSteam');
                $mail->addAddress($user->getEmail(), $user->getUsername());     //Add a recipient

                //Content
                $mail->isHTML(true);                                  //Set email format to HTML
                $mail->Subject = 'Email confirmation - LSteam';
                $mail->Body    = "Hello " . $user->getUsername() . ",<br><br>" . "To finish creating your account you must confirm your email clicking the following link: <br> <a href ='http://localhost:8030/activate?token=" . $user->getToken() . "'>http://localhost:8030/activate?token=" . $user->getToken() . "</a><br><br>See you soon!<br>LSteam";
                $mail->AltBody = "Hello " . $user->getUsername() . ",\n\n" . "To finish creating your account you must confirm your email by going to the following link: \n http://localhost:8030/activate?token=" . $user->getToken() . "\n\nSee you soon!\nLSteam";

                $mail->send();
                $emailSent = true;
            } catch (Exception $e) {
                $emailSent = false;
            }
        }

        $routeParser = RouteContext::fromRequest($request)->getRouteParser();

        return $this->twig->render(
            $response,
            'register.twig',
            [
                'formErrors' => $errors,
                'formData' => $data,
                'formAction' => $routeParser->urlFor("handle-form-register"),
                'formMethod' => "POST",
                'finished' => $finished,
                'emailSent' => $emailSent
            ]
        );
    }

    //email d'activacio del usuari
    public function activation(Request $request, Response $response): Response
    {
        $token = $request->getQueryParams('token')['token'];
        $activation = true;
        $user = $this->userRepository->getUserByToken($token);
        $correcte = empty($user) || !$user->getConfirmed();

        if ($correcte) {
            $this->userRepository->confirmUser($user->getToken());
            $user->setConfirmed(true);
        }

        $mail = new PHPMailer(true);

        //Server settings
        $mail->isSMTP();                                      //Send using SMTP
        $mail->Host       = 'mail.smtpbucket.com';            //Set the SMTP server to send through
        $mail->Port       = 8025;                              //TCP port to connect to, use 465 for `PHPMailer::ENCRYPTION_SMTPS` above

        //Recipients
        $mail->setFrom('no-reply@lsteam.com', 'LSteam');
        $mail->addAddress($user->getEmail(), $user->getUsername());     //Add a recipient

        //Content
        $mail->isHTML(true);                                  //Set email format to HTML
        $mail->Subject = 'Confirmation successful! - LSteam';
        $mail->Body    = "Hello " . $user->getUsername() . ",<br><br>" . "Your email was confirmed successfully and 50&euro; will be added to your wallet when you log in!<br> <a href ='http://localhost:8030/login'>http://localhost:8030/login</a><br><br>See you soon!<br>LSteam";
        $mail->AltBody = "Hello " . $user->getUsername() . ",\n\n" . "Your email was confirmed successfully and 50€ were added to your wallet!\nClick here to log in:\nSee you soon!\nLSteam";

        $mail->send();

        $routeParser = RouteContext::fromRequest($request)->getRouteParser();

        return $this->twig->render(
            $response,
            'register.twig',
            [
                'correcte' => $correcte,
                'activation' => $activation
            ]
        );
    }
}
