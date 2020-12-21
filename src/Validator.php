<?php
namespace App;

class Validator
{
    public function validate($user)
    {
        $errors = [];
        if (empty($user['name'])) {
            $errors['name'] = "Can't be blank name";
        }
        if (empty($user['mail'])) {
            $errors['mail'] = "Can't be blank email";
        }
        if (empty($user['id'])) {
            $errors['id'] = "Can't be blank email";
        }
        return $errors;
    }
}