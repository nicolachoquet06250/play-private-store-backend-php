<?php
namespace PPS\enums;

enum Repos: string {
    case GITHUB = 'github';
    case GITLAB = 'gitlab';

    public static function getLabel(self $value): string {
        return match($value) {
            Repos::GITHUB => 'github',
            Repos::GITLAB => 'gitlab',
            default => null
        };
    }
    
    public static function fromString(string $value) {
        return match($value) {
            'github' => Repos::GITHUB,
            'gitlab' => Repos::GITLAB,
            '"github"' => Repos::GITHUB,
            '"gitlab"' => Repos::GITLAB,
            default => null
        };
    }
}