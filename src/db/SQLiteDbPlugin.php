<?php

namespace PPS\db;

use \PPS\app\{
    DBPlugin,
    Model
};
use \PDO;
use \Exception;
use \PDOException;

class SQLiteDbPlugin extends DBPlugin {
    public function __construct(
        public string $connectionString
    ) {}

    public function getConnectionParameters(): array {
        return [ $this->connectionString ];
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

    public function getAll(string $modelClass): array {
        $request = "SELECT * FROM {$this->getTable($modelClass)}";

        $r = $this->getPDO([
            'db' => $this->getDatabase()
        ])->prepare($request);
        $r->execute();

        $results = $r->fetchAll(PDO::FETCH_ASSOC);

        $finalResult = [];

        $ref = new \ReflectionClass($modelClass);
        $properties = array_reduce($ref->getProperties(), fn(array $r, \ReflectionProperty $c) => $c->isStatic() ? $r : [...$r, $c], []);

        $metaProperties = array_reduce($properties, function(array $r, \ReflectionProperty $c) {
            return [
                ...$r, 
                $c->getName() => array_reduce($c->getAttributes(), function(array $r, $c) {
                    $attribute = $c->newInstance();

                    return [
                        ...$r, 
                        ...array_reduce(
                            (new \ReflectionObject($attribute))->getProperties(), 
                            fn(array $_r, $_c) => [
                                ...$_r, 
                                $_c->getName() => $_c->getValue($attribute)
                            ], [])
                        ];
                }, [])
            ];
        }, []);

        foreach ($results as $i => $result) {
            foreach ($metaProperties as $name => $metaProperty) {
                if (isset($result[$name])) {
                    if (!isset($finalResult[$i])) $finalResult[$i] = [];

                    if (isset($metaProperty['json']) && $metaProperty['json']) {
                        $finalResult[$i][$name] = json_decode($result[$name], true);
                    } else {
                        if (substr($result[$name], 0, 1) === '"' && substr($result[$name], -1, 1) === '"') {
                            $result[$name] = substr($result[$name], 1, -1);
                        }
                        $finalResult[$i][$name] = $result[$name];
                    }
                }
            }

            $finalResult[$i] = $modelClass::fromArray($finalResult[$i]);
        }

        return $finalResult;
    }

    public function getFrom(string $field, mixed $value, string $modelClass): array {
        $request = "SELECT * FROM {$this->getTable($modelClass)} WHERE {$field}=?";

        $r = $this->getPDO([
            'db' => $this->getDatabase()
        ])->prepare($request);
        $r->execute([$value]);

        $results = $r->fetchAll(PDO::FETCH_ASSOC);

        $finalResult = [];

        $ref = new \ReflectionClass($modelClass);
        $properties = array_reduce($ref->getProperties(), fn(array $r, \ReflectionProperty $c) => $c->isStatic() ? $r : [...$r, $c], []);

        $metaProperties = array_reduce($properties, function(array $r, \ReflectionProperty $c) {
            return [
                ...$r, 
                $c->getName() => array_reduce($c->getAttributes(), function(array $r, $c) {
                    $attribute = $c->newInstance();

                    return [
                        ...$r, 
                        ...array_reduce(
                            (new \ReflectionObject($attribute))->getProperties(), 
                            fn(array $_r, $_c) => [
                                ...$_r, 
                                $_c->getName() => $_c->getValue($attribute)
                            ], [])
                        ];
                }, [])
            ];
        }, []);

        foreach ($results as $i => $result) {
            foreach ($metaProperties as $name => $metaProperty) {
                if (isset($result[$name])) {
                    if (!isset($finalResult[$i])) $finalResult[$i] = [];

                    if (isset($metaProperty['json']) && $metaProperty['json']) {
                        $finalResult[$i][$name] = json_decode($result[$name], true);
                    } else {
                        $finalResult[$i][$name] = $result[$name];
                    }
                }
            }

            $finalResult[$i] = $modelClass::fromArray($finalResult[$i]);
        }

        return $finalResult;
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
                    return [...$r, ...$props];
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

        // dump($request);

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

                if ($value || is_string($value) && $value === '' || ((is_int($value) || is_float($value)) && $value === 0)) {
                    if (is_array($value) || is_object($value)) {
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

                if (
                    !in_array($property->getType()->getName(), ['int', 'float', 'string', 'array']) 
                    && in_array('fromString', get_class_methods($property->getType()->getName()))
                ) {
                    $values[$name] = $property->getType()->getName()::getLabel(
                        $property->getType()->getName()::fromString($value)
                    );
                }
            }
        }

        $keys = array_keys($values);
        $values = array_values($values);

        $request .= "(" . implode(', ', $keys) . ") VALUES(" . implode(', ', array_reduce($values, fn($r) => [...$r, '?'], [])) . ")";

        $conn = $this->getPDO($model->getDBVariables());

        //dump($request, $values);

        try {
            $r = $conn->prepare($request);
            $r->execute($values);
        } catch (PDOException $e) {
            preg_match_all(
                '/SQLSTATE\[[0-9]+\]:\ [A-Za-z\ ]+:\ [0-9]+\ (?<constraint_type>[A-Z]+)\ constraint failed:\ (?<table_name>[a-z]+)\.(?<field_name>[a-zA-Z\_]+)/m', 
                $e->getMessage(), 
                $matches, PREG_SET_ORDER, 0
            );
            if (!empty($matches)) {
                $matches = array_reduce(array_keys($matches[0]), fn($r, $c) => is_numeric($c) ? $r : [...$r, $c => $matches[0][$c]], []);
            
                if ($matches['constraint_type'] === 'UNIQUE') {
                    throw new Exception("Le champ {$matches['field_name']} existe déjà");
                }
            }
            
            throw $e;
        }

        $model->id = $conn->lastInsertId();

        return true;
    }

    public function deleteLine(Model $model): bool {
        $id = $model->id;

        $request = "DELETE FROM {$this->getTable($model)} WHERE id={$id}";

        //dump($request);

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
                    if (is_array($value) || is_object($value)) {
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

        //dump($request, $values);

        $r = $this->getPDO($model->getDBVariables())->prepare($request);
        $r->execute($values);

        return true;
    }
}