<?php

namespace PPS\api\controllers;

use \Psr\Http\Message\{
    ResponseInterface as Response,
    ServerRequestInterface as Request
};
use PPS\decorators\{ 
    ApplyMethodAfterInstanciate,
    Controller as RouteGroup, 
    Get, Post, Put, Delete
};
use PPS\models\{ 
    App, 
    User
};
use PPS\{
    enums\ChannelType,
    http\Controller,
    app\No
};
use Exception;

#[RouteGroup('/app')]
class AppController extends Controller {
    public ?int $id = null;
    #[ApplyMethodAfterInstanciate(
        type: ApplyMethodAfterInstanciate::NOTIFIER
    )]
    public ?No $no = null;
    #[ApplyMethodAfterInstanciate(
        type: ApplyMethodAfterInstanciate::BODY
    )]
    public ?array $body = [];

    #[Get('s')]
    public function getAllApps(): array {
        return App::getAll() ?? [];
    }

    #[Get('/{id}')]
    public function getFromId() {
        $result = App::getFromId($this->id);

        if ($result) \http_response_code(404);

        return $result ?? [
            'status' => 404,
            'message' => "L'application recherchée n'existe pas"
        ];
    }

    #[Post()]
    public function createApp() {
        $createdApp = $this->body;
        
        \http_response_code(201);

        $users = array_map(fn(User $c) => $c->id, User::getAll());

        $app = \PPS\models\App::fromArray($createdApp);

        try {
            $app->create();

            $this->no->tify(
                channel: 'notify', 
                type: ChannelType::GIVE, 
                message: [
                    'element' => 'app',
                    'type' => 'created',
                    'appId' => $app->id,
                    'appName' => $app->name,
                    'users' => $users
                ]
            );

            return [
                'message' => "L'application \"{$app->name}\" à bien été créée",
                'notified' => $this->no->hasSocket(),
                'app' => $app
            ];
        } catch (Exception $e) {
            return [
                'status' => 500,
                'message' => $e->getMessage()
            ];
        }
    }

    #[Put('/{id}')]
    public function updateApp() {
        $body = $this->body;
        
        $app = App::getFromId($this->id);

        if ($app) {
            $users = array_reduce(
                User::getAll(), 
                fn(array $r, User $c) => in_array($this->id, $c->followed_apps) ? [...$r, $c->id] : $r, 
                []
            );
            $users = array_reduce($users, fn(array $r, int $c) => in_array($c, $r) ? $r : [...$r, $c], []);

            $app->update($body);

            $this->no->tify(
                channel: 'notify', 
                type: ChannelType::GIVE, 
                message: [
                    'element' => 'app',
                    'type' => 'updated',
                    'appId' => $app->id,
                    'users' => $users
                ]
            );

            return [
                'message' => "L'application \"{$app->name}\" à bien été modifiée",
                'notified' => $this->no->hasSocket(),
                'app' => $app
            ];
        }

        \http_response_code(404);

        return [
            'status' => 404,
            'message' => "L'application avec l'id {$this->id} n'existe pas"
        ];
    }

    #[Delete('/{id}')]
    public function deleteApp() {
        if (App::getFromId($this->id)?->delete()) {
            \http_response_code(204);

            $users = array_reduce(
                User::getAll(), 
                fn(array $r, User $c) => 
                    in_array($this->id, $c->followed_apps) ? [...$r, $c->id] : $r, 
                []
            );
            $users = array_reduce($users, fn(array $r, int $c) => 
                in_array($c, $r) ? $r : [...$r, $c], []);

            $this->no->tify(
                channel: 'notify', 
                type: ChannelType::GIVE, 
                message: [
                    'element' => 'app',
                    'type' => 'deleted',
                    'appId' => $this->id,
                    'appName' => $this->name,
                    'users' => $users
                ]
            );

            return App::getAll();
        } else {
            \http_response_code(500);

            return [
                'status' => 500,
                'message' => "Une erreur est survenue lors de la suppression de l'application"
            ];
        }
    }
}