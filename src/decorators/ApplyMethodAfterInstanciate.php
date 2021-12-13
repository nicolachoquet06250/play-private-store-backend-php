<?php

namespace PPS\decorators;

use \Attribute;

#[Attribute]
class ApplyMethodAfterinstanciate {
    public function __construct(
        public string $method,
        public ?array $data = null,
        public ?array $_this = null
    ) {}
}