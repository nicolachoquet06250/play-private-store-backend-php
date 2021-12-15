<?php

namespace PPS\http;

use \Psr\Http\Message\{
    ResponseInterface as Response,
    ServerRequestInterface as Request
};
use \Exception;
use \ReflectionClass;
use \ReflectionProperty;

abstract class Controller {
    public function __construct(
        protected Request $request, 
        protected Response $response, 
        protected array $args
    ) {
        $this->setPropertiesFromUrlProps();
        $this->dependencyInjectionToProperties();
    }

    private function setPropertiesFromUrlProps() {
        foreach ($this->args as $key => $value) {
            $this->{$key} = $value;
        }
    }

    private function dependencyInjectionToProperties() {
        $currentClass = static::class;

        $ref = new ReflectionClass($currentClass);
        $props = $ref->getProperties(ReflectionProperty::IS_PUBLIC);
        foreach ($props as $prop) {
            $propType = (string)$prop->getType();

            if (!empty(($attrs = $prop->getAttributes(\PPS\decorators\ApplyMethodAfterInstanciate::class)))) {
                $attr = $attrs[0]->newInstance();

                $var = null;
                if (!in_array($propType, ['int', '?int', 'string', '?string', 'array', '?array', 'float', '?float'])) {
                    $this->{$prop->getName()} = new (str_replace('?', '', $propType))();
                } else {
                    foreach ($attr->target as $v) {
                        if ($var === null) {
                            $var = $$v;
                        } else {
                            $var = $var->$v;
                        }
                    }
                }
                
                $params = [];
                if (!is_null($attr->_this)) {
                    $params = [...$params, ...array_reduce($attr->_this, fn($r, $c) => [...$r, $this->{$c}], [])];
                }

                if (!is_null($attr->data)) {
                    $params = [...$params, ...array_reduce(array_keys($attr->data), fn($r, $c) => [...$r, $attr->data[$c]], [])];
                }

                if (is_null($var)) {
                    $this->{$prop->getName()}->{$attr->method}(...$params);
                } else {
                    $this->{$prop->getName()} = $var->{$attr->method}(...$params);
                }
            }
        }
    }
}