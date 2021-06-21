<?php

declare(strict_types=1);

use SallePW\SlimApp\Controller\HomeController;
use SallePW\SlimApp\Controller\RegisterController;
use SallePW\SlimApp\Controller\LoginController;
use SallePW\SlimApp\Controller\ProfileController;
use SallePW\SlimApp\Controller\WalletController;
use SallePW\SlimApp\Controller\StoreController;
use SallePW\SlimApp\Controller\VisitsController;
use SallePW\SlimApp\Middleware\StartSessionMiddleware;
use SallePW\SlimApp\Controller\FriendRequestsController;
use SallePW\SlimApp\Controller\FriendListController;
use SallePW\SlimApp\Controller\WishlistController;

$app->add(StartSessionMiddleware::class);

//HOME
$app->get(
    '/',
    HomeController::class . ':apply'
)->setName('home');

//REGISTER
$app->get(
    '/register',
    RegisterController::class . ":showForm"
);

$app->get(
    '/activate',
    RegisterController::class . ":activation"
);

$app->post(
    '/register',
    RegisterController::class . ":handleFormSubmission"
)->setName('handle-form-register');

//LOGIN
$app->get(
    '/login',
    LoginController::class . ":showForm"
)->setName('login');

$app->post(
    '/login',
    LoginController::class . ":handleFormSubmission"
)->setName('handle-form-login');

$app->post(
    '/logout',
    LoginController::class . ":logout"
)->setName('logout');

$app->group('', function () use ($app) {
    //PROFILE
    $app->get(
        '/profile',
        ProfileController::class . ":showForm"
    );

    $app->post(
        '/profile',
        ProfileController::class . ":handleFormSubmission"
    )->setName('handle-form-profile');

    $app->get(
        '/profile/changePassword',
        ProfileController::class . ":changePasswordForm"
    );

    $app->post(
        '/profile/changePassword',
        ProfileController::class . ":changePassword"
    )->setName('handle-form-changePassword');

    //WALLET
    $app->get(
        '/user/wallet',
        WalletController::class . ":showForm"
    );

    $app->post(
        '/user/wallet',
        WalletController::class . ":handleFormSubmission"
    )->setName('handle-form-wallet');

    //STORE
    $app->get(
        '/store',
        StoreController::class . ":showForm"
    )->setName('store');

    $app->post(
        '/store/buy/{gameId}',
        StoreController::class . ":buyGame"
    )->setName('buy-game');

    $app->get(
        '/user/myGames',
        StoreController::class . ":myGames"
    );

    //FRIEND LIST
    $app->get(
        '/user/friends',
        FriendListController::class . ":showFriendList"
    );

    //FRIEND REQUESTS
    $app->get(
        '/user/friendRequests',
        FriendRequestsController::class . ":showRequests"
    );

    $app->get(
        '/user/friendRequests/send',
        FriendRequestsController::class . ":showForm"
    );

    $app->post(
        '/user/friendRequests/send',
        FriendRequestsController::class . ":sendFriendRequests"
    )->setName('send-request');

    $app->post(
        '/user/friendRequests/accept/{requestId}',
        FriendRequestsController::class . ":acceptFriendRequests"
    )->setName('accept-request');

    $app->post(
        '/user/friendRequests/decline/{requestId}',
        FriendRequestsController::class . ":declineFriendRequests"
    )->setName('decline-request');

    //WISHLIST
    $app->get(
        '/user/wishlist',
        WishlistController::class . ":showWishlist"
    )->setName('wishlist');

    $app->get(
        '/user/wishlist/{gameId}',
        WishlistController::class . ":showDetails"
    )->setName('show-details');

    $app->post(
        '/user/wishlist/{gameId}',
        WishlistController::class . ":wishGame"
    )->setName('wish-game');

    $app->delete(
        '/user/wishlist/{gameId}',
        WishlistController::class . ":deleteWish"
    )->setName('delete-wish');

})->add(StartSessionMiddleware::class);
