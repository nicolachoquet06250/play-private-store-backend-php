<?php

namespace PPS\decorators\db;

use \Attribute;

#[Attribute]
class Integer {
    public function __construct(
        public int $size = 11,
        public bool $primaryKey = false,
        public bool $autoIncrement = false,
        public bool $nullable = false,
        public ?string $default = null,
        public string $type = 'integer'
    ) {}
}