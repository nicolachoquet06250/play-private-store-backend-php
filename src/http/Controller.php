<?php

namespace PPS\http;

use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\ServerRequestInterface as Request;

abstract class Controller {
    public function __construct(
        protected Request $request, 
        protected Response $response, 
        protected array $args
    ) {}

    public function __get(string $key) {
        if ($this->args[$key]) {
            return $this->args[$key]; 
        }
        throw new Exception(static::class . '::$' . $key . ' not found');
    }
}