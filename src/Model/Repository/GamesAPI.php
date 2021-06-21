<?php

declare(strict_types=1);

namespace SallePW\SlimApp\Model\Repository;

use PDO;
use DateTime;
use SallePW\SlimApp\Model\User;
use SallePW\SlimApp\Model\UserRepository;
use SallePW\SlimApp\Model\CacheRepository;
use GuzzleHttp\Client;

class GamesAPI implements CacheRepository{

    public function __construct() 
    {

    }

    public function GetDeals():array{
        $client = new Client([

            'base_uri' => 'https://www.cheapshark.com/api/1.0/',
            'timeout'  => 5.0,
        ]);
        
        $responseGuzzle = $client->request('GET', 'deals');
        $json = json_decode($responseGuzzle->getBody()->getContents());

        foreach ($json as &$jsoni) {
            $jsoni->lastChange = date('d-m-Y', $jsoni->lastChange); 
            $jsoni->releaseDate = date('d-m-Y', $jsoni->releaseDate); 
        }
        
        return $json;
    }
}