<?php

ini_set('display_errors', 1);
// error_reporting(E_ALL);
// Rapporte les erreurs d'exécution de script
error_reporting(E_ERROR | E_WARNING | E_PARSE);
// Rapporter les E_NOTICE peut vous aider à améliorer vos scripts
// (variables non initialisées, variables mal orthographié
//error_reporting(E_ERROR | E_WARNING | E_PARSE | E_NOTICE);

use \PPS\app\{
    Application,
    Model
};
use \PPS\http\Cors;
use \PPS\api\controllers\{
    UserController,
    AppController,
    HomeController
};
use \PPS\middlewares\{
    Router, Json
};
use PPS\db\SQLiteDbPlugin;

require __DIR__ . '/../vendor/autoload.php';

Cors::enable();

define('__ROOT__', realpath(__DIR__ . '/../'));

$dotenv = new \BalintHorvath\DotEnv\DotEnv(__ROOT__);

$app = new Application();

Model::setDBPlugin(new SQLiteDbPlugin());

$app->use(
    new Json, 
    new Router(
        UserController::class, 
        AppController::class, 
        HomeController::class
    )
);

$app->run();