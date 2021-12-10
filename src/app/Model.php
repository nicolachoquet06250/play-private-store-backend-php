<?php

namespace PPS\app;

use \PDO;

abstract class Model {
    protected static array $items = [];
    protected static array $defined = [];
    protected static DBPlugin|null $DBPlugin = null;

    public function __construct() {
        /*if ($this->createTable()) {
            dump("La table {$this->getTable()} à été créé avec succès dans la base de données");
        }*/
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

            if ($itemType === 'string' && !in_array($param->getType()->getName(), ['int', 'float', 'string', 'array']) && in_array('fromString', get_class_methods($param->getType()->getName()))) {
                $finalParams[$position] = $param->getType()->getName()::fromString($item[$param->getName()]);
                continue;
            }

            if ($param->getType()->getName() !== $itemType) {
                throw new \Exception("{$class}::fromArray() method param {$param->getName()} must be of type {$param->getType()->getName()} but is of type {$itemType}");
                break;
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

    public function create(): self {
        static::$items[static::class][] = $this;
        static::$DBPlugin?->createLine($this);
        return $this;
    }
}