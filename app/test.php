<?php

ini_set('display_errors', 1);
//error_reporting(E_ALL);
error_reporting(E_ERROR | E_WARNING | E_PARSE);

use PPS\models\{
    User, App
};
use PPS\app\Model;
use PPS\db\{
    SQLiteDbPlugin,
    MysqlDbPlugin
};
use PPS\enums\Repos;
use \BalintHorvath\DotEnv\DotEnv;

require __DIR__ . '/../vendor/autoload.php';

define('__ROOT__', realpath(__DIR__ . '/../'));

$dotenv = new DotEnv(__ROOT__);

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

if (!(empty(getenv('ENVIRONEMENT')) || getenv('ENVIRONEMENT') === 'dev')) {
    http_response_code(404);

    echo <<<HTML
        <h1>404</h1>

        <p>PAGE NOT FOUND</p>
    HTML;
    return;
}

/*$user = User::fromArray([
    'firstname' => 'Nicolas',
    'lastname' => 'Choquet',
    'email' => 'nchoquet@test.fr',
    'repo_pseudo' => [
        'github' => 'toto',
        'gitlab' => ''
    ],
    'password' => 'toto'
]);*/

foreach (User::defineDefaultFakeData() as $user) {
    try {
        $user->create();
    } catch (Exception $e) {
        dump($e->getMessage(), $user->email);
    }
}

dump(User::getAll());

/*$user->create();

dump($user);

$user->update([
    'lastname' => 'Amgar'
]);

dump($user);*/

/*$user->delete();

dump($user);*/

echo '--------------------------------------------------------------------------------------------------------------------------------------------';

/*$app = App::fromArray([
    'repo_type' => Repos::GITHUB,
    'name' => 'Test',
    'nameSlug' => 'test',
    'repoName' => 'test',
    'logo' => 'https://grafikart.fr/favicon.ico',
    'version' => '1.0.0',
    'versionSlug' => '1-0-0',
    'description' => null,
    'stars' => 3.5,
    'author' => 1
]);*/

/*foreach (App::defineDefaultFakeData() as $app) {
    $app->create();
}*/

dump(App::getAll());

echo '--------------------------------------------------------------------------------------------------------------------------------------------';

$user = User::getFrom('id', 1)[0];

dump($user);
dump('user downloaded apps', $user->getMyDowloadedApps());
dump('user apps', $user->getMyApps());

/*$app->create();

dump($app);

$app->update([
    'name' => 'LOLILOL'
]);

dump($app);*/

/*$app->delete();

dump($app);*/
