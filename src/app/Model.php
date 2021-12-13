<?php

namespace PPS\app;

use \PDO;
use \Exception;

abstract class Model {
    protected static array $items = [];
    protected static array $defined = [];
    protected static DBPlugin|null $DBPlugin = null;

    public function __construct() {
        $this->createTable();
    }

    public function getDBVariables(): array {
        return [
            'db' => $this->getDatabase()
        ];
    }

    protected function getDatabase(): string {
        return static::$DBPlugin?->getDatabase();
    }

    protected function getTable(): string {
        return static::$DBPlugin?->getTable($this);
    }

    public function createTable(): bool {
        return static::$DBPlugin?->createTable($this);
    }

    public static function setDBPlugin(DBPlugin $plugin) {
        static::$DBPlugin = $plugin;
    }

    public function getPDOInstance(): PDO {
        return static::$DBPlugin->getPDO($this->getDBVariables());
    }

    protected static function defineDefaultFakeData(): array {
        return [];
    }

    /**
     * @param array $item
     * @return Model
     */
    public static function fromArray(array $item) {
        $class = static::class;

        $params = (new \ReflectionClass($class))
            ->getConstructor()->getParameters();
        $finalParams = [];

        if (empty($item['id'])) {
            $items = $class::getAll();
            $nextId = count($items) === 0 ? 0 : $items[count($items) - 1]->id + 1;
            $item['id'] = $nextId;
        }

        foreach ($params as $position => $param) {
            $itemType = match(gettype(isset($item[$param->getName()]) ? $item[$param->getName()] : null)) {
                'integer' => 'int',
                'double' => 'float',
                'object' => get_class($item[$param->getName()]),
                default => gettype(isset($item[$param->getName()]) ? $item[$param->getName()] : null)
            };

            if (!isset($item[$param->getName()])) {
                if ($param->isDefaultValueAvailable()) {
                    $finalParams[$position] = $param->getDefaultValue();
                } else if ($param->allowsNull()) {
                    $finalParams[$position] = null;
                }

                continue;
            }

            $paramType = (string)$param->getType();

            if (
                !in_array($paramType, [
                    'int', 'float', 'string', 'array', 
                    '?int', '?float', '?string', '?array'
                ]) 
                && in_array('fromString', get_class_methods($paramType))
            ) {
                $finalParams[$position] = $paramType::fromString($item[$param->getName()]);
                continue;
            }

            if ($paramType !== $itemType) {
                if ($paramType !== '?' . $itemType) {
                    throw new \Exception("{$class}::fromArray() method param {$param->getName()} must be of type {$paramType} but is of type {$itemType}");
                    break;
                }
            }

            $finalParams[$position] = $item[$param->getName()];
        }

        return new $class(...$finalParams);
    }

    /**
     * @return Array<self>
     */
    static public function getAll(): array {
        $class = static::class;

        $results = static::$DBPlugin?->getAll($class);

        if (is_null($results)) {
            if (!isset(static::$items[$class]) || !isset(static::$defined[$class])) {
                static::$items[$class] = $class::defineDefaultFakeData();
                static::$defined[$class] = true;
            }
        } else {
            static::$items[$class] = $results;
            static::$defined[$class] = true;
        }

        return $class::$items[$class];
    }

    static public function getFromId(int $id): Model|null {
        return array_reduce(static::getAll(), fn(Model|null $r, Model $c) => 
            $c->id === $id ? $c : $r, null);
    }

    /**
     * @param string $field
     * @param mixed $value
     * @return self[]
     */
    static public function getFrom(string $field, mixed $value): array {
        return static::$DBPlugin?->getFrom($field, $value, static::class);
    }
    
    public function update(?array $item): bool {
        if (!empty($item)) {
            foreach ($item as $k => $v) {
                if (isset($this->{$k})) {
                    $this->{$k} = $v;
                }
            }

            static::$items[static::class] = array_map(
                fn(Model $a) => $a->id === $this->id ? $this : $a, 
                static::$items[static::class]
            );
        }

        return static::$DBPlugin?->updateLine($this->id, $this);
    }

    public function delete(): bool {
        static::$items[static::class] = array_reduce(
            static::$items[static::class], 
            fn(array $r, Model $c) => $c->id === $this->id ? $r : [...$r, $c], 
            []
        );
        return static::$DBPlugin?->deleteLine($this);
    }

    /**
     * @throws Exception
     * @return self
     */
    public function create(): self {
        static::$items[static::class][] = $this;
        try {
            $r = static::$DBPlugin?->createLine($this);
        } catch (Exception $e) {
            throw $e;
        }

        if (is_bool($r) && !$r) {
            throw new Exception('Une erreur est survenue lors de la création du model');
        }

        return $this;
    }
}