<?php

declare(strict_types=1);

namespace SallePW\SlimApp\Model;

interface CacheRepository
{
    public function GetDeals(): array;
    
}