<?php
declare(strict_types=1);

namespace SallePW\SlimApp\Model;

use GuzzleHttp\Client;

final class APICall
{

    public function __construct()
    {
    }

    public function getGames (): array 
    {
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
