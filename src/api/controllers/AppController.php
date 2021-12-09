<?php

namespace PPS\api\controllers;

use PPS\http\Controller;
use PPS\app\Websocket;
use PPS\decorators\{ 
    Controller as RouteGroup, 
    Get, Post, Put, Delete
};
use PPS\models\{ App, User };
use PPS\enums\ChannelType;

/**
 * @property string $id 
 */
#[RouteGroup('/app')]
class AppController extends Controller {
    
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
        $createdApp = $this->request->getParsedBody();
        [ 'socket'  => $socket ] = $this->request->getQueryParams();

        $socket = $socket ?? 'ws://localhost:8001';

        \http_response_code(201);

        $users = array_map(fn(User $c) => $c->id, User::getAll());

        $app = \PPS\models\App::fromArray($createdApp);

        $app->create();

        (new Websocket($socket))->send(
            channel: 'notify', 
            type: ChannelType::GIVE, 
            data: [
                'type' => 'created',
                'appId' => $app->id,
                'appName' => $app->name,
                'users' => $users
            ]
        );

        return [
            'message' => "L'application \"{$app->name}\" à bien été créée",
            'app' => $app
        ];
    }

    #[Put('/{id}')]
    public function updateApp() {
        $body = $this->request->getParsedBody();
        [ 'socket'  => $socket ] = $this->request->getQueryParams();

        $socket = $socket ?? 'ws://localhost:8001';

        $app = App::getFromId(\intval($this->id));

        if ($app) {
            $users = array_reduce(
                User::getAll(), 
                fn(array $r, User $c) => in_array($this->id, $c->followed_apps) ? [...$r, $c->id] : $r, 
                []
            );
            $users = array_reduce($users, fn(array $r, int $c) => in_array($c, $r) ? $r : [...$r, $c], []);

            $app->update($body);

            (new Websocket($socket))->send(
                channel: 'notify', 
                type: ChannelType::GIVE, 
                data: [
                    'type' => 'updated',
                    'appId' => $app->id,
                    'users' => $users
                ]
            );

            return $app;
        }

        \http_response_code(404);

        return [
            'status' => 404,
            'message' => "L'application avec l'id {$this->id} n'existe pas"
        ];
    }

    #[Delete('/{id}')]
    public function deleteApp() {
        if (App::getFromId(intval($this->id))?->remove()) {
            \http_response_code(204);

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