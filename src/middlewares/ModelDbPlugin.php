<?php

namespace PPS\middlewares;

use \PPS\app\{
    Middleware,
    DBPlugin,
    Model
};

class ModelDbPlugin extends Middleware {
    public function __construct(
        private DBPlugin $plugin,
        private bool $condition
    ) {}
    
    public function manage() {
        if ($this->condition) {
            Model::setDBPlugin($this->plugin);
        }

        return $this->app;
    }
}