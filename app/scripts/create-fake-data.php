<?php

ini_set('display_errors', 1);
error_reporting(E_ERROR | E_WARNING | E_PARSE);

use PPS\models\{
    User, App
};
use PPS\app\Model;
use PPS\db\SQLiteDbPlugin;
use PPS\enums\Repos;
use \BalintHorvath\DotEnv\DotEnv;

require __DIR__ . '/../../vendor/autoload.php';

define('__ROOT__', realpath(__DIR__ . '/../../'));

$dotenv = new DotEnv(__ROOT__);

Model::setDBPlugin(
    new SQLiteDbPlugin(
        'sqlite:' . __ROOT__ . '/{db}.db'
    )
);

if (!empty(getenv('FAKE_DATA_CREATED'))) {
    http_response_code(404);

    echo <<<HTML
        <h1>404</h1>

        <p>FAKE DATA ALREADY CREATED</p>
    HTML;
    return;
}

foreach (User::defineDefaultFakeData() as $user) {
    try {
        $user->create();
    } catch (Exception $e) {
        dump($e->getMessage(), $user->email);
    }
}

echo '--------------------------------------------------------------------------------------------------------------------------------------------';

foreach (App::defineDefaultFakeData() as $app) {
    $app->create();
}

$envFile = file_get_contents(__ROOT__ . '/.env');
$envFile .= "\nFAKE_DATA_CREATED=1";

file_put_contents(__ROOT__ . '/.env', $envFile);
