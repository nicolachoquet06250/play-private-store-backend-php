<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

use PPS\models\{
    User, App
};
use PPS\app\Model;
use PPS\db\SQLiteDbPlugin;
use PPS\enums\Repos;

require __DIR__ . '/../vendor/autoload.php';

define('__ROOT__', realpath(__DIR__ . '/../'));

Model::setDBPlugin(new SQLiteDbPlugin());

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
    $user->create();
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

foreach (App::defineDefaultFakeData() as $app) {
    $app->create();
}

dump(App::getAll());

/*$app->create();

dump($app);

$app->update([
    'name' => 'LOLILOL'
]);

dump($app);*/

/*$app->delete();

dump($app);*/
