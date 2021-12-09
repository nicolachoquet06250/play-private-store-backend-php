<?php

namespace PPS\decorators\db;

use \Attribute;

#[Attribute]
class Varchar {
    public function __construct(
        public int $size = 255,
        public bool $nullable = false,
        public ?string $default = null,
        public string $type = 'varchar'
    ) {}
}