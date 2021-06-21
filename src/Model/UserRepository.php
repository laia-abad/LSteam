<?php
declare(strict_types=1);

namespace SallePW\SlimApp\Model;

interface UserRepository
{
    public function saveUser(User $user): void;
    
    public function inUse($info): bool;

    public function getLastUser(): User;

    public function confirmUser($token): void;

    public function getUserByToken($token): User;

    public function loginUser($info, $password): User;

    public function updateMoney($username, $amount): void;

    public function updatePicture($username, $picture): void;

    public function updatePhone($username, $phone): void;

    public function updatePassword($username, $newPassword): void;
    
    public function checkPassword($username): string;

    public function saveGame($username, $gameId): void;

    public function wishGame($username, $gameId): void;

    public function deleteWishGame($username, $gameId): void;

    public function getWishlistFromUser($username): array;

    public function getGamesFromUser($username): array;

    public function getFriendsFromUser($username): array;

    public function getFriendRequestsFromUser($username): array;

    public function getFriendRequestsForUser($username): array;

    public function checkUserExists($username): bool;

    public function sendFriendRequest($username_sender, $username_reciever, $requestId): void;

    public function acceptFriendRequest($username, $date): void;

    public function declineFriendRequest($requestId): void;

    public function checkRequestId($username, $requestId): bool;
}