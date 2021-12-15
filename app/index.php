<?php
ini_set('display_errors', 0);

use PPS\app\{
    Application,
    Model
};
use PPS\api\controllers\{
    UserController,
    AppController,
    CommentController,
    HomeController
};
use PPS\middlewares\{
    Router, Json, Cors, 
    ModelDbPlugin
};
use PPS\db\{
    SQLiteDbPlugin,
    MysqlDbPlugin
};
use \BalintHorvath\DotEnv\DotEnv;

require __DIR__ . '/../vendor/autoload.php';

define('__ROOT__', realpath(__DIR__ . '/../'));

$dotenv = new DotEnv(__ROOT__);

if (empty(getenv('ENVIRONEMENT')) || getenv('ENVIRONEMENT') === 'dev') {
    ini_set('display_errors', 1);
    // error_reporting(E_ALL);
    // Rapporte les erreurs d'exécution de script
    error_reporting(E_ERROR | E_WARNING | E_PARSE);
    // Rapporter les E_NOTICE peut vous aider à améliorer vos scripts
    // (variables non initialisées, variables mal orthographié
    //error_reporting(E_ERROR | E_WARNING | E_PARSE | E_NOTICE);
}

(new Application())
    ->use(new Cors())
    ->use(
        new ModelDbPlugin(
            new SQLiteDbPlugin(
                connectionString: 'sqlite:' . __ROOT__ . '/{db}.db'
            ),
            condition: getenv('ENVIRONEMENT') === 'dev'
        )
    )
    ->use(
        new ModelDbPlugin(
            new MysqlDbPlugin(
                host: getenv('DB_HOST'),
                database: getenv('DB_NAME'),
                username: getenv('DB_USERNAME'),
                password: getenv('DB_PASSWORD')
            ),
            condition: !(getenv('ENVIRONEMENT') === 'dev')
        )
    )
    ->use(new Json)
    ->use((new Router())
        ->use(UserController::class)
        ->use(AppController::class)
        ->use(CommentController::class)
        //->use(HomeController::class)
    )->run();