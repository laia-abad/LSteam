<?php


declare(strict_types=1);

namespace SallePW\SlimApp\Model\Repository;

use PDO;
use SallePW\SlimApp\Model\User;
use SallePW\SlimApp\Model\UserRepository;
use SallePW\SlimApp\Model\CacheGetGames;
use SallePW\SlimApp\Model\Repository\GamesAPI;
use SallePW\SlimApp\Model\CacheRepository;
use DateTime;
use SallePW\SlimApp\Model\Friend;

class DecoratorGamesAPI implements CacheRepository {

    protected $decoratee;

    public function __construct(GamesAPI $decoratee) 
    {
        $this->decoratee = $decoratee;
    
    }

    public function GetDeals() : array
    {
        return $this->decoratee->GetDeals();
    
    }
}