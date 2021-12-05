<?php

namespace PPS\middlewares;

use \PPS\app\Middleware;

class Json extends Middleware {
    public function manage() {
        $this->app->add([new JsonBodyParserMiddleware, 'process']);

        return $this->app;
    }
}