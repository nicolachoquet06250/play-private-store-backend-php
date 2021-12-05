<?php

namespace PPS\api\controllers;

use PPS\http\Controller;
use PPS\decorators\Controller as RouteGroup;
use PPS\decorators\{ Get, Post };
use PPS\models\App;

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
        [ 'name' => $name ] = $this->request->getParsedBody();
        $queryString = $this->request->getUri()->getQuery();
        $query = $queryString ? array_reduce(explode('&', $queryString), function($r, $c) {
            if (strstr($c, '=')) {
                $r[explode('=', $c)[0]] = explode('=', $c)[1];
            } else {
                $r[$c] = true;
            }
            return $r;
        }) : [];

        $socket_url = $query['socket'] ?? 'ws://localhost:8001';

        $appId = 3;

        \Ratchet\Client\connect($socket_url)->then(function($conn) use($appId) {
            $conn->send(\json_encode([
                'channel' => 'notify',
                'type' => 'give',
                'data' => [
                    'appId' => $appId
                ]
            ]));

            $conn->close();
        }, function ($e) {
            echo "Could not connect: {$e->getMessage()}\n";
        });

        \http_response_code(201);

        return [
            'message' => "L'application \"{$name}\" à bien été créée"
        ];
    }
}