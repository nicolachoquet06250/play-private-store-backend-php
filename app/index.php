<?php

ini_set('display_errors', 0);
// error_reporting(E_ALL);
// Rapporte les erreurs d'exécution de script
error_reporting(E_ERROR | E_WARNING | E_PARSE);
// Rapporter les E_NOTICE peut vous aider à améliorer vos scripts
// (variables non initialisées, variables mal orthographié
//error_reporting(E_ERROR | E_WARNING | E_PARSE | E_NOTICE);

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

define('__ROOT__', realpath(__DIR__ . '/../'));

$dotenv = new \BalintHorvath\DotEnv\DotEnv(__ROOT__);

$app = new Application();

$app->use(
    new Json, 
    new Router(
        UserController::class, 
        AppController::class, 
        HomeController::class
    )
);

$app->run();