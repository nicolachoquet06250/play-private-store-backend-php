<?php

namespace PPS\middlewares;

use \PPS\app\Middleware;
use \PPS\http\Router as Routage;
use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\ServerRequestInterface as Request;

class Router extends Middleware {
    /**
     * @property string[] $usedRoutes
     */
    private array $usedRoutes = [];  

    public function __construct(
        string ...$usedRoutes
    ) {
        $this->usedRoutes = $usedRoutes;
    }

    public function use(string $usedRoute): self {
        $this->usedRoutes[] = $usedRoute;
        return $this;
    }

    public function manage() {
        foreach ($this->usedRoutes as $controller) {
            $r = new \ReflectionClass($controller);

            foreach ($r->getAttributes() as $attribute) {
                $methods = $r->getMethods();

                foreach ($methods as $m) {
                    if (substr($m->getName(), 0, 2) !== '__') {
                        Routage::setRoute($controller, $m);
                    }
                }
            }
        }

        foreach (Routage::$routes as $controller => $methods) {
            foreach ($methods as $method) {
                $baseRoute = $method->getDeclaringClass()->getAttributes()[0]->newInstance()->route; 
                $methodRoute = $method->getAttributes()[0]->newInstance()->route;
                $verb = $method->getAttributes()[0]->newInstance()->verb;

                $route = str_replace('//', '/', $baseRoute . $methodRoute);

                $this->app->{$verb}($route, function (Request $request, Response $response, $args) use($controller, $method) {
                    $ctrl = new $controller($request, $response, $args);
                    $result = $ctrl->{$method->getName()}();

                    if (is_array($result) || is_object($result)) {
                        header('Content-Type: application/json;charset=utf-8');
                        $result = json_encode($result);
                    }

                    if ($result) {
                        $response->getBody()->write($result);
                    } 
                    return $response;
                });
            }
        }

        return $this->app;
    }
}