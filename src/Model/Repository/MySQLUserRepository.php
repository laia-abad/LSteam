<?php

declare(strict_types=1);

namespace SallePW\SlimApp\Model\Repository;

use PDO;
use SallePW\SlimApp\Model\User;
use SallePW\SlimApp\Model\UserRepository;
use DateTime;
use SallePW\SlimApp\Model\Friend;

final class MysqlUserRepository implements UserRepository
{

    private PDOSingleton $database;

    public function __construct(PDOSingleton $database)
    {
        $this->database = $database;
    }

    public function saveUser(User $user): void
    {
        $query = <<<'QUERY'
        INSERT INTO User(username, email, password, birthday, phone, token)
        VALUES(:username, :email, :password, :birthday, :phone, :token)
        QUERY;
        $statement = $this->database->connection()->prepare($query);

        $email = $user->getEmail();
        $password = $user->getPassword();
        $username = $user->getUsername();
        $phone = $user->getPhone();
        $birthday = $user->getBirthday()->format('Y-m-d');
        $token = $user->getToken();

        $statement->bindParam('email', $email, PDO::PARAM_STR);
        $statement->bindParam('password', $password, PDO::PARAM_STR);
        $statement->bindParam('username', $username, PDO::PARAM_STR);
        $statement->bindParam('birthday', $birthday, PDO::PARAM_STR);
        $statement->bindParam('phone', $phone, PDO::PARAM_STR);
        $statement->bindParam('token', $token, PDO::PARAM_STR);

        $statement->execute();
    }

    public function inUse($info): bool
    {
        //Checks that the email isn't in use
        $statement = $this->database->connection()->prepare('SELECT email FROM User WHERE email LIKE :email OR username LIKE :username');
        $statement->bindParam('email', $info, PDO::PARAM_STR);
        $statement->bindParam('username', $info, PDO::PARAM_STR);
        $statement->execute();
        $results = $statement->fetchAll(PDO::FETCH_ASSOC);
        
        echo('HOLA');

        if (!empty($results)) {
            return true;
        } else {
            return false;
        }
    }

    public function getLastUser(): User
    {
        $statement = $this->database->connection()->prepare('SELECT * FROM User ORDER BY token DESC LIMIT 1');
        $statement->execute();
        $results = $statement->fetchAll(PDO::FETCH_ASSOC);

        $user = User::withParameters(
            $results[0]['username'],
            $results[0]['email'],
            $results[0]['password'],
            DateTime::createFromFormat('Y-m-d', $results[0]['birthday']),
            $results[0]['phone'] != null ? $results[0]['phone'] : '',
            $results[0]['token']
        );

        return $user;
    }

    public function confirmUser($token): void
    {
        $statement = $this->database->connection()->prepare('UPDATE User
            SET confirmed = 1, wallet = 50
            WHERE token = :token;');
        $statement->bindParam('token', $token, PDO::PARAM_STR);
        $statement->execute();
    }

    public function getUserByToken($token): User
    {
        $statement = $this->database->connection()->prepare('SELECT * FROM User WHERE token = :token');
        $statement->bindParam('token', $token, PDO::PARAM_STR);
        $statement->execute();
        $results = $statement->fetchAll(PDO::FETCH_ASSOC);

        $user = User::withParameters(
            $results[0]['username'],
            $results[0]['email'],
            $results[0]['password'],
            DateTime::createFromFormat('Y-m-d', $results[0]['birthday']),
            $results[0]['phone'] != null ? $results[0]['phone'] : '',
            $results[0]['token']
        );
        $user->setWallet((float)$results[0]['wallet']);
        $user->setConfirmed((bool)$results[0]['confirmed']);
        $user->setPicture($results[0]['picture'] ? $results[0]['picture'] : '');

        return $user;
    }

    public function loginUser($info, $password): User
    {
        $statement = $this->database->connection()->prepare('SELECT * FROM User WHERE (email LIKE :email OR username LIKE :username) 
        AND password LIKE :password AND confirmed = 1');
        $statement->bindParam('email', $info, PDO::PARAM_STR);
        $statement->bindParam('username', $info, PDO::PARAM_STR);
        $statement->bindParam('password', $password, PDO::PARAM_STR);
        $statement->execute();
        $results = $statement->fetchAll(PDO::FETCH_ASSOC);

        if (empty($results)) {
            $user = new User();
        } else {
            $user = User::withParameters(
                $results[0]['username'],
                $results[0]['email'],
                $results[0]['password'],
                DateTime::createFromFormat('Y-m-d', $results[0]['birthday']),
                $results[0]['phone'] != null ? $results[0]['phone'] : '',
                $results[0]['token']
            );
            $user->setWallet((float)$results[0]['wallet']);
            $user->setConfirmed((bool)$results[0]['confirmed']);
            $user->setPicture($results[0]['picture'] ? $results[0]['picture'] : '');
        }
        return $user;
    }

    public function updateMoney($username, $amount): void
    {
        $statement = $this->database->connection()->prepare('UPDATE User
            SET wallet = :amount
            WHERE username LIKE :username;');
        $statement->bindParam('amount', $amount, PDO::PARAM_STR);
        $statement->bindParam('username', $username, PDO::PARAM_STR);
        $statement->execute();
    }

    public function updatePicture($username, $picture): void
    {
        $statement = $this->database->connection()->prepare('UPDATE User
            SET picture = :picture
            WHERE username LIKE :username;');
        $statement->bindParam('picture', $picture, PDO::PARAM_STR);
        $statement->bindParam('username', $username, PDO::PARAM_STR);
        $statement->execute();
    }

    public function updatePhone($username, $phone): void
    {
        $statement = $this->database->connection()->prepare('UPDATE User
            SET phone = :phone
            WHERE username LIKE :username;');
        $statement->bindParam('phone', $phone, PDO::PARAM_STR);
        $statement->bindParam('username', $username, PDO::PARAM_STR);
        $statement->execute();
    }

    public function checkPassword($username): string
    {
        $statement = $this->database->connection()->prepare('SELECT password FROM User WHERE username LIKE :username;');
        $statement->bindParam('username', $username, PDO::PARAM_STR);
        $statement->execute();
        $results = $statement->fetchAll(PDO::FETCH_ASSOC);

        return $results[0]['password'];
    }

    public function updatePassword($username, $newPassword): void
    {
        $statement = $this->database->connection()->prepare('UPDATE User
            SET password = :password
            WHERE username LIKE :username;');
        $statement->bindParam('password', $newPassword, PDO::PARAM_STR);
        $statement->bindParam('username', $username, PDO::PARAM_STR);
        $statement->execute();
    }

    public function saveGame($username, $gameId): void
    {

        $statement = $this->database->connection()->prepare('SELECT gameId FROM Game WHERE username LIKE :username AND gameId = :gameId;');
        $statement->bindParam('username', $username, PDO::PARAM_STR);
        $statement->bindParam('gameId', $gameId, PDO::PARAM_STR);
        $statement->execute();
        $results = $statement->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($results)) {
            $statement = $this->database->connection()->prepare('UPDATE Game
            SET wishlist = 0
            WHERE gameId = :gameId;');

            $statement->bindParam('gameId', $gameId, PDO::PARAM_STR);
            $statement->execute();
        } else {
            $query = <<<'QUERY'
            INSERT INTO Game(username, gameId, wishlist)
            VALUES(:username, :gameId, 0)
            QUERY;
            $statement = $this->database->connection()->prepare($query);

            $statement->bindParam('username', $username, PDO::PARAM_STR);
            $statement->bindParam('gameId', $gameId, PDO::PARAM_STR);

            $statement->execute();
        }
    }

    public function wishGame($username, $gameId): void
    {
        $query = <<<'QUERY'
        INSERT INTO Game(username, gameId, wishlist)
        VALUES(:username, :gameId, 1)
        QUERY;
        $statement = $this->database->connection()->prepare($query);

        $statement->bindParam('username', $username, PDO::PARAM_STR);
        $statement->bindParam('gameId', $gameId, PDO::PARAM_STR);

        $statement->execute();
    }

    public function getWishlistFromUser($username): array
    {
        $statement = $this->database->connection()->prepare('SELECT gameId FROM Game WHERE username LIKE :username AND wishlist = 1;');
        $statement->bindParam('username', $username, PDO::PARAM_STR);
        $statement->execute();
        $results = $statement->fetchAll(PDO::FETCH_ASSOC);
        return $results;
    }

    public function deleteWishGame($username, $gameId): void
    {
        $statement = $this->database->connection()->prepare('DELETE FROM Game WHERE username LIKE :username AND gameId = :gameId AND wishlist = 1;');
        $statement->bindParam('username', $username, PDO::PARAM_STR);
        $statement->bindParam('gameId', $gameId, PDO::PARAM_STR);

        $statement->execute();
    }

    public function getGamesFromUser($username): array
    {
        $statement = $this->database->connection()->prepare('SELECT gameId FROM Game WHERE username LIKE :username AND wishlist = 0;');
        $statement->bindParam('username', $username, PDO::PARAM_STR);
        $statement->execute();
        $results = $statement->fetchAll(PDO::FETCH_ASSOC);
        return $results;
    }

    public function getFriendsFromUser($username): array
    {
        $friends = [];

        $statement = $this->database->connection()->prepare('SELECT * FROM Friends WHERE username_reciever LIKE :username AND accept_date IS NOT NULL;');

        $statement->bindParam('username', $username, PDO::PARAM_STR);
        $statement->execute();
        $results = $statement->fetchAll(PDO::FETCH_ASSOC);

        foreach ($results as &$result) {
            $friends[] = Friend::withParameters(
                $result['username_sender'],
                $results[0]['accept_date'],
                $result['requestId'],
                (bool)$result['declined']
            );
        }

        $statement = $this->database->connection()->prepare('SELECT * FROM Friends WHERE username_sender LIKE :username AND accept_date IS NOT NULL;');
        $statement->bindParam('username', $username, PDO::PARAM_STR);
        $statement->execute();
        $results = $statement->fetchAll(PDO::FETCH_ASSOC);

        foreach ($results as &$result) {
            $friends[] = Friend::withParameters(
                $result['username_reciever'],
                $results[0]['accept_date'],
                $result['requestId'],
                (bool)$result['declined']
            );
        }

        return $friends;
    }

    public function getFriendRequestsForUser($username): array
    {
        $requests = [];
        $statement = $this->database->connection()->prepare('SELECT * FROM Friends WHERE username_reciever LIKE :username AND accept_date IS NULL AND declined = 0;');

        $statement->bindParam('username', $username, PDO::PARAM_STR);
        $statement->execute();
        $results = $statement->fetchAll(PDO::FETCH_ASSOC);

        foreach ($results as &$result) {
            $requests[] = Friend::withParameters(
                $result['username_sender'],
                '',
                $result['requestId'],
                (bool)$result['declined']
            );
        }
        return $requests;
    }

    public function getFriendRequestsFromUser($username): array
    {
        $requests = [];
        $statement = $this->database->connection()->prepare('SELECT * FROM Friends WHERE username_sender LIKE :username AND accept_date IS NULL AND declined = 0;');

        $statement->bindParam('username', $username, PDO::PARAM_STR);
        $statement->execute();
        $results = $statement->fetchAll(PDO::FETCH_ASSOC);

        foreach ($results as &$result) {
            $requests[] = Friend::withParameters(
                $result['username_reciever'],
                '',
                $result['requestId'],
                (bool)$result['declined']
            );
        }
        return $requests;
    }

    public function checkUserExists($username): bool
    {
        $statement = $this->database->connection()->prepare('SELECT username FROM User WHERE username LIKE :username;');
        $statement->bindParam('username', $username, PDO::PARAM_STR);
        $statement->execute();
        $results = $statement->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($results)) {
            return true;
        } else {
            return false;
        }
    }

    public function sendFriendRequest($username_sender, $username_reciever, $requestId): void
    {
        $query = <<<'QUERY'
        INSERT INTO Friends(username_sender, username_reciever, requestId)
        VALUES(:username_sender, :username_reciever, :requestId)
        QUERY;
        $statement = $this->database->connection()->prepare($query);

        $statement->bindParam('username_sender', $username_sender, PDO::PARAM_STR);
        $statement->bindParam('username_reciever', $username_reciever, PDO::PARAM_STR);
        $statement->bindParam('requestId', $requestId, PDO::PARAM_STR);

        $statement->execute();
    }

    public function acceptFriendRequest($requestId, $date): void
    {
        $statement = $this->database->connection()->prepare('UPDATE Friends
            SET accept_date = :date
            WHERE requestId = :requestId;');
        $statement->bindParam('date', $date, PDO::PARAM_STR);
        $statement->bindParam('requestId', $requestId, PDO::PARAM_STR);
        $statement->execute();
    }

    public function declineFriendRequest($requestId): void
    {
        $statement = $this->database->connection()->prepare('UPDATE Friends
            SET declined = 1
            WHERE requestId = :requestId;');
        $statement->bindParam('requestId', $requestId, PDO::PARAM_STR);
        $statement->execute();
    }

    public function checkRequestId($username, $requestId): bool
    {
        $statement = $this->database->connection()->prepare('SELECT username_reciever FROM Friends WHERE requestId = :requestId AND username_reciever LIKE :username AND accept_date IS NULL AND declined = 0;');
        $statement->bindParam('requestId', $requestId, PDO::PARAM_STR);
        $statement->bindParam('username', $username, PDO::PARAM_STR);
        $statement->execute();
        $results = $statement->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($results)) {
            return true;
        } else {
            return false;
        }
    }
}
