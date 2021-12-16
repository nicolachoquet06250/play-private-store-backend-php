<?php

namespace PPS\app;

use \Slim\Factory\AppFactory;
use \Slim\App;

class Application {
    /**
     * @property Middleware[] $middlewares
     */
    private array $middlewares = [];  

    public function __construct(
        private ?App $app = null
    ) {
        $this->app = AppFactory::create();
    }

    public function use(Middleware ...$middleware) {
        $this->middlewares = [
            ...$this->middlewares, 
            ...$middleware
        ];
        return $this;
    }

    public function run() {
        foreach ($this->middlewares as $middleware) {
            $this->app = $middleware($this->app);
        }

        try { 
            $this->app->run();
        } catch (\Exception $e) {
            header('Content-Type: application/json;charset=utf-8');
            \http_response_code(404);

            echo json_encode([
                'status' => 404,
                'message' => (empty(getenv('ENVIRONEMENT')) || getenv('ENVIRONEMENT') === 'dev' ? $e->getMessage() : "La page que vous recherchez n'existe pas")
            ]);
        }
    }
}