<?php

declare(strict_types=1);

namespace SallePW\SlimApp\Model\Repository;

use PDO;
use SallePW\SlimApp\Model\User;
use SallePW\SlimApp\Model\UserRepository;
use SallePW\SlimApp\Model\CacheRepository;


class CacheGames extends DecoratorGamesAPI {


    public function GetDeals() : array
    {
        //comprobem si existeix el fitxer
        if(file_exists(__DIR__ . '/../cache/deals.txt')){

            $file = file_get_contents('./deals.txt');
            return json_decode($file);
        }else{
            //creem el fitxer
            return $this->NewFile();
        }
    
    }

    private function NewFile() : array
    {
        //creem un nou fitxer
        $file = fopen(__DIR__ . '/../cache/deals.txt', "r");

        $deals = $this->decoratee->GetDeals();
        if($file == false) return $deals;

        //converim l'array a json
        $deals_encoded = json_encode($deals);
        //convertim el json a fitxer
        file_put_contents(__DIR__ . '/../cache/deals.txt', $deals_encoded);

        return $deals;
    
    }

}