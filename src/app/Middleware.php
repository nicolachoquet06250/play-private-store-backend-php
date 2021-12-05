<?php

namespace PPS\app;

use \Slim\Factory\AppFactory;
use \Slim\App;

abstract class Middleware {
    protected App $app;

    public abstract function manage();

    public function __invoke(App $app) {
        $this->app = $app;
        return $this->manage();
    }
}