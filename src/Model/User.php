<?php
declare(strict_types=1);

namespace SallePW\SlimApp\Model;

use DateTime;

final class User
{
    private string $token;
    private string  $username;
    private string  $email;
    private string  $password;
    private DateTime $birthday;
    private string  $phone;
    private float $wallet;
    private bool $confirmed;
    private string  $picture;
    
    //constructor
    public function __construct() {}

    public static function withParameters(string $username, string $email, string $password, DateTime $birthday, string $phone, string $token) {
        $instance = new self();
        $instance->loadParameters($username, $email, $password, $birthday, $phone, $token);
        return $instance;
    }


    protected function loadParameters(string $username, string $email, string $password, DateTime $birthday, string $phone, string $token)
    {
        $this->username = $username;
        $this->email = $email;
        $this->password = $password;
        $this->birthday = $birthday;
        $this->phone = $phone;
        $this->token = $token;
    }

    //setters i getters
    public function setToken(string $token): self
    {
        $this->token = $token;
        return $this;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function setBirthday(string $birthday): self
    {
        $this->birthday = $birthday;
        return $this;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;
        return $this;
    }

    public function setPhone(string $phone): self
    {
        $this->phone = $phone;
        return $this;
    }

    public function setWallet(float $wallet): self
    {
        $this->wallet = $wallet;
        return $this;
    }

    public function setConfirmed(bool $confirmed): self
    {
        $this->confirmed = $confirmed;
        return $this;
    }

	public function setPicture(string $picture): self
    {
        $this->picture = $picture;
        return $this;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getPhone(): string
    {
        return $this->phone;
    }

    public function getBirthday(): DateTime
    {
        return $this->birthday;
    }

    public function getWallet(): float
    {
        return $this->wallet;
    }

    public function getConfirmed(): bool
    {
        return $this->confirmed;
    }

	public function getPicture(): string
    {
        return $this->picture;
    }
}