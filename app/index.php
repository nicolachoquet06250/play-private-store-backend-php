<?php

use Psr\Http\Message\{
    ResponseInterface as Response,
    ServerRequestInterface as Request
};
use \PPS\app\Application;
use \PPS\api\controllers\{
    UserController,
    AppController,
    HomeController
};
use \PPS\middlewares\{
    Router, Json
};

require __DIR__ . '/../vendor/autoload.php';

header("Access-Control-Allow-Origin: *");

$app = new Application();

$app->use(new Json, new Router(
    UserController::class, 
    AppController::class, 
    HomeController::class
));

$app->run();