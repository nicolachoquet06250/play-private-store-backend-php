<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

use PPS\models\User;
use PPS\models\App;
use PPS\app\Model;
use PPS\db\SQLiteDbPlugin;

require __DIR__ . '/../vendor/autoload.php';

define('__ROOT__', realpath(__DIR__ . '/../'));

Model::setDBPlugin(new SQLiteDbPlugin());

$user = User::fromArray([
    'firstname' => 'Nicolas',
    'lastname' => 'Choquet',
    'email' => 'nchoquet@test.fr',
    'repo_pseudo' => [
        'github' => 'toto',
        'gitlab' => ''
    ],
    'password' => 'toto'
]);

/*if ($user->createTable()) {
    dump('La table user à été créé avec succès dans la base de données');
}*/

$user->create();

dump($user);

$user->update([
    'lastname' => 'Amgar'
]);

dump($user);

/*$user->delete();

dump($user);*/

echo '--------------------------------------------------------------------------------------------------------------------------------------------';

$app = App::fromArray([
    'repo_type' => PPS\enums\Repos::GITHUB,
    'name' => 'Test',
    'nameSlug' => 'test',
    'repoName' => 'test',
    'logo' => 'https://grafikart.fr/favicon.ico',
    'version' => '1.0.0',
    'versionSlug' => '1-0-0',
    'description' => null,
    'stars' => 3.5,
    'author' => 1
]);

/*if ($app->createTable()) {
    dump('La table app à été créé avec succès dans la base de données');
}*/

$app->create();

dump($app);

$app->update([
    'name' => 'LOLILOL'
]);

dump($app);

/*$app->delete();

dump($app);*/
