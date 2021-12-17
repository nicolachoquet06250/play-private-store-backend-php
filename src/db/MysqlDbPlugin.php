<?php

namespace PPS\db;

use \PPS\app\{
    DBPlugin,
    Model
};
use \PDO;
use \Exception;
use \PDOException;

class MysqlDbPlugin extends DBPlugin {
    private string $connectionString;

    public function __construct(
        private string $host,
        private string $database,
        private string $username,
        private string $password
    ) {
        $this->connectionString = "mysql:dbname={$this->database};host={$this->host}";
    }

    public function getConnectionParameters(): array {
        return [ 
            $this->connectionString, 
            $this->username, 
            $this->password
        ];
    }

    public function getPDO(array $variables): PDO {
        return new PDO(...$this->getConnectionParameters());
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

            if (!$propertiesMeta[$name]['default'] && $property->getValue($model)) {
                $value = $property->getDefaultValue();
                
                if (in_array('json', array_keys($propertiesMeta[$name])) && $propertiesMeta[$name]['json']) {
                    $value = json_encode($value);
                }
                
                $propertiesMeta[$name]['default'] = $value;

                if (($propertiesMeta[$name]['default'] === null || $propertiesMeta[$name]['default'] === "null") && empty($propertiesMeta[$name]['nullable'])) {
                    unset($propertiesMeta[$name]['default']);
                }
            }
        }

        $request = "CREATE TABLE IF NOT EXISTS `{$this->getTable($model)}` (\n";
        $cmp = 0;
        $hasAutoIncrement = false;
        foreach ($propertiesMeta as $propName => $propMeta) {
            $type = strtoupper($propMeta['type']);
            $primaryKey = !empty($propMeta['primaryKey']) && $propMeta['primaryKey'] ? ' PRIMARY KEY' : '';
            $autoIncrement = !empty($propMeta['autoIncrement']) && $propMeta['autoIncrement'] ? ' AUTO_INCREMENT' : '';
            $hasAutoIncrement = !$hasAutoIncrement && !empty($propMeta['autoIncrement']) && $propMeta['autoIncrement'] ? true : $hasAutoIncrement;
            $size = (!empty($propMeta['autoIncrement']) && $propMeta['autoIncrement']) || empty($propMeta['size']) ? '' : "({$propMeta['size']})";
            $nullable = empty($propMeta['nullable']) || !$propMeta['nullable'] ? ' NOT NULL' : '';
            $defaultValue = !empty($propMeta['default']) ? " DEFAULT " . (is_numeric($propMeta['default']) ? $propMeta['default'] : '"' . $propMeta['default'] . '"') : '';
            $unique = !empty($propMeta['unique']) && $propMeta['unique'] ? ' UNIQUE' : '';
            $cmp++;
            $end = $cmp === count($propertiesMeta) ? '' : ',';
            $request .= "   $propName $type{$size}{$primaryKey}{$autoIncrement}{$defaultValue}{$nullable}{$unique}{$end}\n";
        }
        $request .= ")";

        // dump($request);

        $r = $this->getPDO($model->getDBVariables())->prepare($request);
        $r->execute();

        return true;
    }

    public function createLine(Model &$model): bool {
        $r = new \ReflectionObject($model);

        $request = "INSERT INTO `{$this->getTable($model)}` ";

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

                //dump($name, $value);

                if ($value || (is_string($value) && $value === '') || ((is_int($value) || is_float($value)) && $value === 0)) {
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

        // dump($request, $values);

        try {
            $r = $conn->prepare($request);
            $r->execute($values);
        } catch (PDOException $e) {
            preg_match_all(
                '/(?<constraint_type>Duplicate\ entry)\ \'(?<field_value>[\w\d\@\.\_\-]+)\'\ for key \'(?<field_name>[\w\d\_]+)\'/m', 
                $e->getMessage(), 
                $matches, PREG_SET_ORDER, 0
            );

            if (!empty($matches)) {
                $matches = array_reduce(array_keys($matches[0]), fn($r, $c) => is_numeric($c) ? $r : [...$r, $c => $matches[0][$c]], []);
            
                if ($matches['constraint_type'] === 'Duplicate entry') {
                    throw new Exception("La valeur \"{$matches['field_value']}\" du champ \"{$matches['field_name']}\" existe déjà");
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