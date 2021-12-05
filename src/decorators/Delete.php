<?php

namespace PPS\decorators;

use \Attribute;

#[Attribute()]
class Delete {
    public string $verb = 'delete';

    public function __construct(
        public string $route = ''
    ) {}
}