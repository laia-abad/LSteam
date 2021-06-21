<?php
declare(strict_types=1);



use DI\Container;
use Slim\Views\Twig;
use SallePW\SlimApp\Controller\HomeController;
use SallePW\SlimApp\Controller\RegisterController;
use SallePW\SlimApp\Controller\LoginController;
use SallePW\SlimApp\Controller\WalletController;
use SallePW\SlimApp\Controller\StoreController;
use SallePW\SlimApp\Controller\FriendListController;
use SallePW\SlimApp\Controller\WishlistController;
use SallePW\SlimApp\Controller\FriendRequestsController;

use Psr\Container\ContainerInterface;
use SallePW\SlimApp\Model\Repository\MySQLUserRepository;
use SallePW\SlimApp\Model\Repository\PDOSingleton;
use SallePW\SlimApp\Model\Repository\CacheGames;

use SallePW\SlimApp\Model\UserRepository;
use SallePW\SlimApp\Model\CacheRepository;

use SallePW\SlimApp\Controller\ProfileController;

use SallePW\SlimApp\Controller\FlashController;
use SallePW\SlimApp\Model\Repository\GamesAPI;
use SallePW\SlimApp\Repository\DecoratorGamesAPI;


use Symfony\Component\Dotenv\Dotenv;
use Slim\Flash\Messages;


$dotenv = new Dotenv();

$dotenv->load(__DIR__ . '/../.env');

$container = new Container();

//Twigs de Templates
$container->set(
    'view',
    function () {
        return Twig::create(__DIR__ . '/../templates', ['cache' => false]);
    }
);

//HomeController
$container->set(
    HomeController::class,
    function (ContainerInterface $c) {
        $controller = new HomeController($c->get("view"), $c->get(UserRepository::class),$c->get("flash"));
        return $controller;
    }
);

//RegisterController
$container->set(
    RegisterController::class,
    function (ContainerInterface $c) {
        $controller = new RegisterController($c->get("view"), $c->get(UserRepository::class),$c->get("flash"));
        return $controller;
    }
);

//LoginController
$container->set(
    LoginController::class,
    function (ContainerInterface $c) {
        $controller = new LoginController($c->get("view"), $c->get(UserRepository::class),$c->get("flash"));
        return $controller;
    }
);

//ProfileController
$container->set(
    ProfileController::class,
    function (ContainerInterface $c) {
        $controller = new ProfileController($c->get("view"), $c->get(UserRepository::class),$c->get("flash"));
        return $controller;
    }
);

//WalletController
$container->set(
    WalletController::class,
    function (ContainerInterface $c) {
        $controller = new WalletController($c->get("view"), $c->get(UserRepository::class),$c->get("flash"));
        return $controller;
    }
);

//StoreController
$container->set(
    StoreController::class,
    function (ContainerInterface $c) {
        $controller = new StoreController($c->get("view"), $c->get(UserRepository::class),$c->get("flash"), $c->get(CacheRepository::class));
        return $controller;
    }
);

//FriendListController
$container->set(
    FriendListController::class,
    function (ContainerInterface $c) {
        $controller = new FriendListController($c->get("view"), $c->get(UserRepository::class),$c->get("flash"));
        return $controller;
    }
);

//FriendRequestsController
$container->set(
    FriendRequestsController::class,
    function (ContainerInterface $c) {
        $controller = new FriendRequestsController($c->get("view"), $c->get(UserRepository::class),$c->get("flash"));
        return $controller;
    }
);

//WishlistController
$container->set(
    WishlistController::class,
    function (ContainerInterface $c) {
        $controller = new WishlistController($c->get("view"), $c->get(UserRepository::class),$c->get("flash"));
        return $controller;
    }
);

$container->set('db', function () {
    return PDOSingleton::getInstance(
        $_ENV['MYSQL_ROOT_USER'],
        $_ENV['MYSQL_ROOT_PASSWORD'],
        $_ENV['MYSQL_HOST'],
        $_ENV['MYSQL_PORT'],
        $_ENV['MYSQL_DATABASE']
    );
});

//Interficie UserRepository
$container->set(UserRepository::class, function (ContainerInterface $container) {
    return new MySQLUserRepository($container->get('db'));
});

//Cache API
$container->set(GamesAPI::class, function (ContainerInterface $container) {
    return new GamesAPI($container->get('db'));
});

//Interficie CacheRepository
$container->set(CacheRepository::class, function (ContainerInterface $container) {
    return new CacheGames($container->get(GamesAPI::class));
});


$container->set(
    'flash',
    function () {
        return new Messages();
    }
);

//FlashController
$container->set(
    FlashController::class,
    function (Container $c) {
        $controller = new FlashController($c->get("view"), $c->get("flash"));
        return $controller;
    }
);

