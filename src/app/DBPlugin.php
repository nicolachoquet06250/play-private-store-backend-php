<?php

namespace PPS\app;

use \PDO;

abstract class DBPlugin {
    public abstract function getConnectionParameters(): array;

    public abstract function createTable(Model $model): bool;

    public abstract function getPDO(array $variables): PDO;

    public function getDatabase(): string {
        $composerFile = file_get_contents(__ROOT__ . '/composer.json');
        $composerFile = json_decode($composerFile, true);

        $appName = $composerFile['name'];
        return explode('/', $appName)[1];
    }

    public function getTable(Model|string $model): string {
        $class = is_string($model) ? $model : $model::class;
        $table = explode('\\', $class)[count(explode('\\', $class)) - 1];
        return strtolower($table);
    }

    public abstract function getFrom(string $field, mixed $value, string $modelClass): array;

    public abstract function getAll(string $modelClass): array;

    public abstract function createLine(Model &$model): bool;

    public abstract function deleteLine(Model $model): bool;

    public abstract function updateLine(int $id, Model $model): bool;
}