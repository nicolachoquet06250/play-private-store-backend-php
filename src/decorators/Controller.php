<?php

namespace PPS\decorators;

use \Attribute;

#[Attribute]
class Controller {
    public function __construct(
        public string $route = '/'
    ) {}
}