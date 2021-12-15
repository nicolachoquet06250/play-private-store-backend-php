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

#[RouteGroup('/app/{appId}/comment')]
class CommentController extends Controller {
    public int $appId;
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
    public function getAllComments(): array {
        return App::getFromId($this->appId)->comments ?? [];
    }

    #[Post()]
    public function createComment() {
        $comment = $this->body;

        $app = App::getFromId($this->appId);

        if (!is_null($app)) {
            if($app->update([
                'comments' => [
                    ...$app->comments, 
                    $comment
                ]
            ])) {

                $author = User::getFrom('id', $comment['author'])[0];

                \http_response_code(201);

                $users = array_reduce(
                    User::getAll(), 
                    fn(array $r, User $c) => in_array($this->appId, $c->followed_apps) && !in_array($c->id, $r) ? [...$r, $c->id] : $r, 
                    []
                );
                //$users = array_reduce($users, fn(array $r, int $c) => in_array($c, $r) ? $r : [...$r, $c], []);

                $this->no->tify(
                    channel: 'notify', 
                    type: ChannelType::GIVE, 
                    message: [
                        'element' => 'comment',
                        'type' => 'created',
                        'appId' => $app->id,
                        'appName' => $app->name,
                        'comment' => $comment,
                        'users' => $users
                    ]
                );

                return [
                    'message' => "{$author->firstname} {$author->lastname} à posté un nouveau commentaire dans l'application \"{$app->name}\"",
                    'notified' => $this->no->hasSocket(),
                    'app' => $app
                ];
            }
        }
    }

    #[Delete('/{id}')]
    public function deleteComment() {
        $app = App::getFromId($this->appId);

        if (!is_null($app)) {
            $comments = $app->comments;
            $comments = array_reduce(
                $comments, 
                fn($r, $c) => [
                    'comments' => (($r['cmp'] + 1) === $this->id ? $r['comments'] : [...$r['comments'], $c]), 
                    'cmp' => $r['cmp'] + 1
                ], 
                [
                    'comments' => [], 
                    'cmp' => 0
                ]
            )['comments'];

            if ($app->update([ 'comments' => $comments ])) {
                \http_response_code(204);

                $users = array_reduce(
                    User::getAll(), 
                    fn(array $r, User $c) => in_array($this->appId, $c->followed_apps) && !in_array($c->id, $r) ? [...$r, $c->id] : $r, 
                    []
                );

                $this->no->tify(
                    channel: 'notify',
                    type: ChannelType::GIVE,
                    message: [
                        'element' => 'comment',
                        'type' => 'deleted',
                        'appId' => $app->id,
                        'appName' => $app->name,
                        'users' => $users
                    ]
                );

                return $app;
            }
        }

        \http_response_code(500);

        return [
            'status' => 500,
            'message' => "Une erreur est survenue lors de la suppression du commentaire"
        ];
    }
}