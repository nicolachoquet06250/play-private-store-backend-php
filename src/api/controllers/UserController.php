<?php

namespace PPS\api\controllers;

use PPS\decorators\{ 
    ApplyMethodAfterInstanciate,
    Controller as RouteGroup,
    Get, Post, Put, Delete
};
use PPS\{
    app\No,
    models\User,
    http\Controller,
    enums\ChannelType
};

#[RouteGroup('/user')]
class UserController extends Controller {
    public ?int $appId = null;
    public ?int $id = null;
    public ?string $email = null;
    public ?string $password = null;
    #[ApplyMethodAfterInstanciate(
        method: 'setRequest',
        _this: [ 'request' ]
    )]
    public ?No $no = null;

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

        $user = User::getFromId($this->id);

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
        if (User::getFromId($this->id)?->delete()) {
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

    #[Post('/{id}/follow/{appId}')]
    public function followApp() {
        $user = User::getFromId($this->id);

        if (!is_null($user)) {
            $followedApps = $user->followed_apps;
            $followedApps = [...$followedApps, $this->appId];

            if ($user->update([ 'followed_apps' => $followedApps ])) {
                $this->no->tify(
                    channel: 'notify',
                    type: ChannelType::GIVE,
                    message: [
                        'element' => 'user_followed_apps',
                        'type' => 'updated',
                        'users' => [$user]
                    ]
                );

                return [
                    'message' => "L'utilisateur à bien été modifié",
                    'notified' => $this->no->hasSocket(),
                    'user' => $user
                ];
            }
        }
    }

    #[Delete('/{id}/unfollow/{appId}')]
    public function unfollowApp() {
        $user = User::getFromId($this->id);

        if (!is_null($user)) {
            $followedApps = array_reduce($user->followed_apps, fn($r, $c) => $c === $this->appId ? $r : [...$r, $c], []);

            if ($user->update([ 'followed_apps' => $followedApps ])) {
                $this->no->tify(
                    channel: 'notify',
                    type: ChannelType::GIVE,
                    message: [
                        'element' => 'user_followed_apps',
                        'type' => 'updated',
                        'users' => [$user]
                    ]
                );

                return [
                    'message' => "L'utilisateur à bien été modifié",
                    'notified' => $this->no->hasSocket(),
                    'user' => $user
                ];
            }
        }
    }
}