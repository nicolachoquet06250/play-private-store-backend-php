<?php

namespace PPS\http;

class Router {
    public static array $routes = [];

    static function setRoute(string $controller, \ReflectionMethod $method) {
        if (!in_array($controller, array_keys(static::$routes))) {
            static::$routes[$controller] = [];
        }
        static::$routes[$controller][] = $method;
    }
}