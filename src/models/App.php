<?php

namespace PPS\models;

use \PPS\enums\Repos; 
use \PPS\app\Model;

class App extends Model {
    public function __construct(
        public int $id,
        public Repos $repo_type,
        public string $name,
        public string $nameSlug,
        public string $repoName,
        public string $logo,
        public string $version,
        public string $versionSlug,
        public ?string $description,
        public float $stars,
        public int $author,
        /**
         * @param string[] $screenshots 
         */
        public array $screenshots = [],
        /**
         * @param string[] $permissions 
         */
        public array $permissions = [],
        /**
         * @param string[] $categories 
         */
        public array $categories = [],
        /**
         * @param Comment[] $comments 
         */
        public array $comments = []
    ) {}

    public static function defineDefaultFakeData(): array {
        return [
            new App(
                1, Repos::GITHUB, 'Budget Management 1', 
                'budget-management', 'budget-management-apk', 
                'https://thumbs.dreamstime.com/z/vecteur-d-ic%C3%B4ne-de-calcul-argent-budget-encaissant-le-logo-illustration-symbole-financier-paiement-152384739.jpg',
                '0.1.0', '0-1-0', "apks signés générés pour l'application budget-management",
                3.5, 1, [], [], [
                    'budget',
                    'budgetaire',
                    'monnaitaire',
                    'argent'
                ], [
                new Comment(
                    1, 
                    'Je suis très satisfait de cette application.', 
                    3.5, 
                    '2021-11-24'
                    )
                ]
            ),
            new App(
                2, Repos::GITHUB, 'Budget Management 2', 
                'budget-management', 'budget-management-apk', 
                'https://thumbs.dreamstime.com/z/vecteur-d-ic%C3%B4ne-de-calcul-argent-budget-encaissant-le-logo-illustration-symbole-financier-paiement-152384739.jpg',
                '0.1.0', '0-1-0', "apks signés générés pour l'application budget-management",
                4, 2, [], [], [
                    'budget',
                    'budgetaire',
                    'monnaitaire',
                    'argent'
                ], [
                    new Comment(
                        0, 
                        'Je suis très satisfait de cette application.', 
                        4, 
                        '2021-11-24'
                    )
                ]
            ),
            new App(
                3, Repos::GITLAB, 'Budget Management 3', 
                'budget-management', 'budget-management-apk', 
                'https://thumbs.dreamstime.com/z/vecteur-d-ic%C3%B4ne-de-calcul-argent-budget-encaissant-le-logo-illustration-symbole-financier-paiement-152384739.jpg',
                '0.1.0', '0-1-0', "apks signés générés pour l'application budget-management",
                2.5, 1, [], [], [
                    'budget',
                    'budgetaire',
                    'monnaitaire'
                ], [
                    new Comment(
                        1, 'Je suis très satisfait de cette application.', 
                        2.5, '2021-11-24'
                    )
                ]
            )
        ];
    }
}