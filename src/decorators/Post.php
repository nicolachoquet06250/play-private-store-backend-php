<?php

namespace PPS\decorators;

use \Attribute;

#[Attribute()]
class Post {
    public string $verb = 'post';

    public function __construct(
        public string $route = ''
    ) {}
}