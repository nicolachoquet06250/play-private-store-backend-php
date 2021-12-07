<?php

namespace PPS\api\controllers;

use PPS\http\Controller;
use PPS\decorators\Controller as RouteGroup;
use PPS\decorators\{ Get, Post, Put };
use PPS\models\{ App, User };

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
        [ 'name'    => $name ]   = $this->request->getParsedBody();

        \http_response_code(201);

        return [
            'message' => "L'application \"{$name}\" à bien été créée"
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

            \Ratchet\Client\connect($socket)
            ->then(function($conn) use($app, $users) {
                $conn->send(\json_encode([
                    'channel' => 'notify',
                    'type' => 'give',
                    'data' => [
                        'appId' => $app->id,
                        'users' => $users
                    ]
                ]));

                $conn->close();
            }, function ($e) {
                echo "Could not connect: {$e->getMessage()}\n";
            });

            return $app;
        }

        \http_response_code(404);

        return [
            'status' => 404,
            'message' => "L'application avec l'id {$this->id} n'existe pas"
        ];
    }
}