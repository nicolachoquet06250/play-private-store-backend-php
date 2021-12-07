<?php

namespace PPS\models;

use \PPS\enums\Repos; 
use \PPS\app\Model;

class User extends Model {
    public function __construct(
        public int $id,
        public string $firstname,
        public string $lastname,
        public string $email,
        /**
         * @param Array<Repos, string> $repo_pseudo
         */
        public array $repo_pseudo,
        public string $password,
        /**
         * @param Array<int>
         */
        public array $followed_apps = []
    ) {}

    protected static function defineDefaultFakeData(): array {
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