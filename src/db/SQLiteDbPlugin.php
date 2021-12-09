<?php

namespace PPS\db;

use \PPS\app\{
    DBPlugin,
    Model
};
use \PDO;

class SQLiteDbPlugin extends DBPlugin {
    public function getConnectionParameters(): array {
        return [
            'sqlite:' . __ROOT__ . '/{db}.db'
        ];
    }

    public function getPDO(array $variables): PDO {
        $connectionArray = $this->getConnectionParameters();

        foreach ($variables as $key => $value) {
            foreach ($connectionArray as $i => $_value) {
                $connectionArray[$i] = str_replace("{{$key}}", $value, $_value);
            }
        }

        return new PDO(...$connectionArray);
    }

    public function createTable(Model $model): bool {
        $r = new \ReflectionObject($model);
        $properties = $r->getProperties();
        $properties = array_reduce($properties, fn(array $r, \ReflectionProperty $c) => $c->isStatic() ? $r : [...$r, $c], []);
        
        $propertiesMeta = [];

        foreach ($properties as $property) {
            $name = $property->getName();
            $attributes = array_reduce($property->getAttributes(), fn(array $r, $c) => [...$r, $c->newInstance()], []);
            $propertiesMeta[$name] = [
                ...array_reduce($attributes, function(array $r, $c) {
                    $ref = new \ReflectionObject($c);
                    $props = array_reduce($ref->getProperties(), function($_r, $_c) use($c) {
                        $_r[$_c->getName()] = $_c->getValue($c);
                        return $_r;
                    }, []);
                    return $props;
                }, [])
            ];
        }

        $request = "CREATE TABLE IF NOT EXISTS {$this->getTable($model)} (\n";
        $cmp = 0;
        $hasAutoIncrement = false;
        foreach ($propertiesMeta as $propName => $propMeta) {
            $type = strtoupper($propMeta['type']);
            $primaryKey = !empty($propMeta['primaryKey']) && $propMeta['primaryKey'] ? ' PRIMARY KEY' : '';
            $autoIncrement = !empty($propMeta['autoIncrement']) && $propMeta['autoIncrement'] ? ' AUTOINCREMENT' : '';
            $hasAutoIncrement = !$hasAutoIncrement && !empty($propMeta['autoIncrement']) && $propMeta['autoIncrement'] ? true : $hasAutoIncrement;
            $size = (!empty($propMeta['autoIncrement']) && $propMeta['autoIncrement']) || empty($propMeta['size']) ? '' : "({$propMeta['size']})";
            $unique = !empty($propMeta['unique']) && $propMeta['unique'] ? ' UNIQUE' : '';
            $cmp++;
            $end = $cmp === count($propertiesMeta) ? '' : ',';
            $request .= "   $propName $type{$size}{$primaryKey}{$autoIncrement}{$unique}{$end}\n";
        }
        $withoutRowid = $hasAutoIncrement ? '' : ' WITHOUT ROWID';
        $request .= "){$withoutRowid}";

        //dump($request);

        $r = $this->getPDO($model->getDBVariables())->prepare($request);
        $r->execute();

        return true;
    }

    public function createLine(Model &$model): bool {
        $r = new \ReflectionObject($model);

        $request = "INSERT INTO {$this->getTable($model)}";

        $values = [];

        foreach ($r->getProperties() as $property) {
            $name = $property->getName();
            if ($name !== 'id' && !$property->isStatic()) {
                $value = $property->getValue($model);
                $attributes = array_reduce($property->getAttributes(), fn(array $r, $c) => [...$r, $c->newInstance()], []);
                $attributes = array_reduce($attributes, function(array $r, $c) {
                    $ref = new \ReflectionObject($c);
                    $props = array_reduce($ref->getProperties(), function($_r, $_c) use($c) {
                        $_r[$_c->getName()] = $_c->getValue($c);
                        return $_r;
                    }, []);
                    return $props;
                }, []);

                if ($value || gettype($value) === 'string' && $value === '') {
                    if (gettype($value) === 'array' || gettype($value) === 'object') {
                        $value = json_encode($value);
                    }
                    $values[$name] = $value;
                } else if ($attributes['nullable']) {
                    $values[$name] = null;
                } else if ($attributes['default']) {
                    $values[$name] = $attributes['default'];
                } else {
                    throw new \Exception("Le champ '{$name}' doit être remplis pour créer un {$this->getTable($model)} !");
                }
            }
        }

        $keys = array_keys($values);
        $values = array_values($values);

        $request .= "(" . implode(', ', $keys) . ") VALUES(" . implode(', ', array_reduce($values, fn($r) => [...$r, '?'], [])) . ")";

        $conn = $this->getPDO($model->getDBVariables());

        dump($request, $values);

        $r = $conn->prepare($request);
        $r->execute($values);

        $model->id = $conn->lastInsertId();

        return true;
    }

    public function deleteLine(Model $model): bool {
        $id = $model->id;

        $request = "DELETE FROM {$this->getTable($model)} WHERE id={$id}";

        dump($request);

        $r = $this->getPDO($model->getDBVariables())->prepare($request);
        $r->execute();

        return true;
    }

    public function updateLine(int $id, Model $model): bool {
        $r = new \ReflectionObject($model);

        $request = "UPDATE {$this->getTable($model)}";

        $values = [];

        foreach ($r->getProperties() as $property) {
            $name = $property->getName();
            if ($name !== 'id' && !$property->isStatic()) {
                $value = $property->getValue($model);
                $attributes = array_reduce($property->getAttributes(), fn(array $r, $c) => [...$r, $c->newInstance()], []);
                $attributes = array_reduce($attributes, function(array $r, $c) {
                    $ref = new \ReflectionObject($c);
                    $props = array_reduce($ref->getProperties(), function($_r, $_c) use($c) {
                        $_r[$_c->getName()] = $_c->getValue($c);
                        return $_r;
                    }, []);
                    return $props;
                }, []);

                if ($value) {
                    if (gettype($value) === 'array' || gettype($value) === 'object') {
                        $value = json_encode($value);
                    }
                    $values[$name] = $value;
                } else if ($attributes['nullable']) {
                    $values[$name] = null;
                } else if ($attributes['default']) {
                    $values[$name] = $attributes['default'];
                } else {
                    throw new \Exception("Le champ '{$name}' doit être remplis pour créer un {$this->getTable($model)} !");
                }
            }
        }

        $keys = array_keys($values);
        $values = array_values($values);

        foreach ($keys as $i => $key) {
            if ($i === 0) {
                $request .= " SET ";
            }

            $request .= "{$key} = ?";

            if ($i < count($keys) - 1) {
                $request .= ", ";
            }
        }

        $request .= "WHERE id={$id}";

        dump($request, $values);

        $r = $this->getPDO($model->getDBVariables())->prepare($request);
        $r->execute($values);

        return true;
    }
}