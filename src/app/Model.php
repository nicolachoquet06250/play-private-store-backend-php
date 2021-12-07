<?php

namespace PPS\app;

abstract class Model {
    protected static array $items = [];
    protected static array $defined = [];

    protected static function defineDefaultFakeData(): array {
        return [];
    }

    /**
     * @return Array<self>
     */
    static public function getAll(): array {
        $class = static::class;

        if (!isset(static::$items[$class]) || !isset(static::$defined[$class])) {
            static::$items[$class] = $class::defineDefaultFakeData();
            static::$defined[$class] = true;
        }

        return $class::$items[$class];
    }

    static public function getFromId(int $id): Model|null {
        return array_reduce(static::getAll(), fn(Model|null $r, Model $c) => 
            $c->id === $id ? $c : $r, null);
    }
    
    public function update(?array $item): bool {
        if ($item) {
            foreach ($item as $k => $v) {
                if (isset($this->{$k})) {
                    $this->{$k} = $v;
                }
            }

            static::$items = array_map(fn(Model $a) => $a->id === $this->id ? $this : $a, static::$items);
        }

        return true;
    }

    public function delete(): bool {
        static::$items = array_reduce(static::$items, fn(array $r, Model $c) => $c->id === $this->id ? $r : [...$r, $c], []);
        return true;
    }
}