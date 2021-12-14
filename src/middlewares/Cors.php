<?php

namespace PPS\middlewares;

use PPS\app\Middleware;
use PPS\http\Cors as HttpCors;

class Cors extends Middleware {
    public function manage() {
        HttpCors::enable();

        return $this->app;
    }
}