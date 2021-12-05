<?php

namespace PPS\decorators;

use \Attribute;

#[Attribute()]
class Get {
    public string $verb = 'get';

    public function __construct(
        public string $route = ''
    ) {}
}