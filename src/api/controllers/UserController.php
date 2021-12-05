<?php

namespace PPS\api\controllers;

use PPS\http\Controller;
use PPS\decorators\Controller as RouteGroup;
use PPS\decorators\{ Get, Post };
use PPS\models\User; 

/**
 * @property string $email
 * @property string $password
 */
#[RouteGroup('/user')]
class UserController extends Controller {

    #[Get('s')]
    public function getAllUsers(): array {
        return User::getAll() ?? [];
    }

    #[Get('/{email}/{password}')]
    public function getFromEmailAndPassword() {
        $result = User::getFromEmailAndPassword(str_replace('+', '.', $this->email), $this->password);

        if ($result) \http_response_code(404);
        
        return $result ?? [
            'status' => 404,
            'message' => "L'utilisateur recherché n'existe pas"
        ];
    }

    #[Post()]
    public function createUser() {
        [
           'firstname' => $firstname,
           'lastname' => $lastname
        ] = $this->request->getParsedBody();
        http_response_code(201);
        
        return [
            'message' => "L'utilisateur {$firstname} {$lastname} à bien été créé"
        ];
    }
}