<?php
declare(strict_types=1);

namespace SallePW\SlimApp\Model;

final class Validations
{
    //constructor
    public function __construct()
    {
    }

    //valoidem la contrasenya
    public function validatePassword (&$errors, $password, $passwordRepeat): bool 
    {
        $error = false;
        if (strlen($password) <= 6) {
            $errors['password'] = "The password must be more than 6 characters long.";
            $error = true;
         
        } elseif ((!preg_match('#[0-9]+#', $password) || !preg_match('#[a-z]+#', $password) || !preg_match('#[A-Z]+#', $password))) {
            $errors['password'] = "The password must contain both upper and lower case letters and numbers.";
            $error = true;
            
        } elseif (strcmp($password, $passwordRepeat)) {
            $errors['passwordRepeat'] = "The passwords don't match.";
            $error = true;
        } 
        return $error;
    }

    //validem el telefon
    public function validatePhone (&$errors, $phone): void
    {
        if (!empty($phone) && (!preg_match('/(\+34|0034|34)?[ -]*(6|7)[ -]*([0-9][ -]*){8}/', $phone) && !preg_match('/(\+34|0034|34)?[ -]*(6|7)[ -]*([0-9][ -]*){8}/', $phone))) {
            $errors['phone'] = "The phone number must be a valid Spanish number";
           
        } 
    }
}
