<?php

namespace PPS\decorators\db;

use \Attribute;

#[Attribute]
class Real {
    public function __construct(
        public ?int $size = null,
        public bool $nullable = false,
        public ?string $default = null,
        public string $type = 'real'
    ) {}
}