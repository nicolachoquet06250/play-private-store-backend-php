<?php

namespace PPS\decorators\db;

use \Attribute;

#[Attribute]
class Json {
    public function __construct(
        public bool $json = true,
        public int $size = 255,
        public bool $nullable = false,
        public ?string $default = null,
        public string $type = 'varchar'
    ) {}
}