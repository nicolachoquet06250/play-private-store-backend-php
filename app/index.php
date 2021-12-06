<?php

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

define('__ROOT__', realpath(__DIR__ . '/../'));

$dotenv = new \BalintHorvath\DotEnv\DotEnv(__ROOT__);

header("Access-Control-Allow-Origin: *");

$app = new Application();

$app->use(new Json, new Router(
    UserController::class, 
    AppController::class, 
    HomeController::class
));

$app->run();