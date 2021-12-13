<?php

ini_set('display_errors', 0);
// error_reporting(E_ALL);
// Rapporte les erreurs d'exécution de script
error_reporting(E_ERROR | E_WARNING | E_PARSE);
// Rapporter les E_NOTICE peut vous aider à améliorer vos scripts
// (variables non initialisées, variables mal orthographié
//error_reporting(E_ERROR | E_WARNING | E_PARSE | E_NOTICE);

use PPS\app\{
    Application,
    Model
};
use PPS\api\controllers\{
    UserController,
    AppController,
    HomeController,
    CommentController
};
use PPS\middlewares\{
    Router, Json
};
use PPS\http\Cors;
use PPS\db\{
    SQLiteDbPlugin,
    MysqlDbPlugin
};
use \BalintHorvath\DotEnv\DotEnv;

require __DIR__ . '/../vendor/autoload.php';

Cors::enable();

define('__ROOT__', realpath(__DIR__ . '/../'));

$dotenv = new DotEnv(__ROOT__);

$app = new Application();

if (getenv('ENVIRONEMENT') === 'dev') {
    Model::setDBPlugin(
        new SQLiteDbPlugin(
            'sqlite:' . __ROOT__ . '/{db}.db'
        )
    );
} else {
    Model::setDBPlugin(
        new MysqlDbPlugin(
            host: getenv('DB_HOST'),
            database: getenv('DB_NAME'),
            username: getenv('DB_USERNAME'),
            password: getenv('DB_PASSWORD')
        )
    );
}

$app->use(
    new Json, 
    new Router(
        UserController::class, 
        AppController::class,
        CommentController::class, 
        HomeController::class
    )
);

$app->run();