<?php

namespace PPS\models;

use \PPS\enums\Repos; 
use \PPS\app\Model;
use \PPS\decorators\db\{
    Integer, Varchar,
    Json, Unique
};

class User extends Model {
    public function __construct(
        #[Integer(
            primaryKey: true,
            autoIncrement: true
        )]
        public int $id,
        #[Varchar]
        public string $firstname,
        #[Varchar]
        public string $lastname,
        #[Unique]
        #[Varchar]
        public string $email,
        #[Json]
        /**
         * @param Array<Repos, string> $repo_pseudo
         */
        public array $repo_pseudo,
        #[Varchar]
        public string $password,
        #[Json(
            default: '[]'
        )]
        /**
         * @param Array<int>
         */
        public array $followed_apps = []
    ) {
        parent::__construct();
    }

    public static function defineDefaultFakeData(): array {
        return [
            new User(1, 'Nicolas', 'Choquet', 'nchoquet@norsys.fr',[ 
                'github' => 'nicolachoquet06250',
                'gitlab' => 'nicolachoquet06250'
            ] , 'nchoquet', [1]),
            new User(2, 'Jonhatan', 'Boyer', 'jboyer@norsys.fr',[ 
                'github' => 'grafikart',
                'gitlab' => ''
            ], 'grafikart', [1])
        ];
    }

    static public function getFromEmailAndPassword(string $email, string $password): User|null {
        return array_reduce(static::getAll(), fn(User|null $r, User $c) => 
            $c->email === $email && $c->password === $password ? $c : $r, null);
    }
}