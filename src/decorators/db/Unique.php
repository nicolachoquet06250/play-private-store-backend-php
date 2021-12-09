<?php

namespace PPS\decorators\db;

use \Attribute;

#[Attribute]
class Unique {
    public function __construct(
        public bool $unique = true
    ) {}
}