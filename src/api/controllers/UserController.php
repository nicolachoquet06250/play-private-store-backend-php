<?php

namespace PPS\api\controllers;

use PPS\http\Controller;
use PPS\decorators\Controller as RouteGroup;
use PPS\decorators\{ Get, Post, Put, Delete };
use PPS\models\User; 

/**
 * @property string $id
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
        $createdUser = $this->request->getParsedBody();
        [
            'firstname' => $firstname, 
            'lastname' => $lastname
        ] = $createdUser;

        try {
            http_response_code(201);

            $user = User::fromArray($createdUser);

            $user->create();
            
            return [
                'message' => "L'utilisateur {$firstname} {$lastname} à bien été créé",
                'user' => $user
            ];
        } catch (\Exception $e) {
            http_response_code(500);
            
            return [
                'status' => 500,
                'message' => "L'utilisateur que vous tentez de créer n'est pas complet"
            ];
        }
    }

    #[Put('/{id}')]
    public function updateUser() {
        $body = $this->request->getParsedBody();

        $user = User::getFromId(\intval($this->id));

        if ($user) {
            $user->update($body);

            return $user;
        }

        \http_response_code(404);

        return [
            'status' => 404,
            'message' => "L'utilisateur avec l'id {$this->id} n'existe pas"
        ];
    }

    #[Delete('/{id}')]
    public function deleteUser() {
        if (User::getFromId(intval($this->id))?->remove()) {
            \http_response_code(204);

            return User::getAll();
        } else {
            \http_response_code(500);

            return [
                'status' => 500,
                'message' => "Une erreur est survenue lors de la suppression de l'utilisateur"
            ];
        }
    }
}