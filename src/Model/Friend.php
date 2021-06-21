<?php
declare(strict_types=1);

namespace SallePW\SlimApp\Model;

use DateTime;

final class Friend
{
    //declarem variables
    private string $requestId;
    private string  $username;
    private string  $accept_date;
    private bool  $declined; 

    //constructor
    public function __construct() {}

    public static function withParameters(string $username, string $accept_date, string $requestId, bool $declined) {
        $instance = new self();
        $instance->loadParameters($username, $accept_date, $requestId, $declined);
        return $instance;
    }


    protected function loadParameters(string $username, string $accept_date, string $requestId, bool $declined)
    {
        $this->username = $username;
        $this->accept_date = $accept_date;
        $this->requestId = $requestId;
        $this->declined = $declined;
    }

    //getters i setters
    
    public function setRequestId(string $requestId): self
    {
        $this->requestId = $requestId;
        return $this;
    }

    public function getRequestId(): string
    {
        return $this->requestId;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;
        return $this;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setDeclined(bool $declined): self
    {
        $this->declined = $declined;
        return $this;
    }

    public function getDeclined(): bool
    {
        return $this->declined;
    }

    public function setAcceptDate(string $accept_date): self
    {
        $this->accept_date = $accept_date;
        return $this;
    }

    public function getAcceptDate(): string
    {
        return $this->accept_date;
    }
}