<?php

namespace PPS\decorators;

use \Attribute;

#[Attribute()]
class Put {
    public string $verb = 'put';

    public function __construct(
        public string $route = ''
    ) {}
}