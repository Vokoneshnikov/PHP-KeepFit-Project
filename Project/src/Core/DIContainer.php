<?php

namespace App\Core;

class DIContainer
{
    private array $instances = [];
    public function set(string $id, $instance): void
    {
        $this->instances[$id] = $instance;
    }
    public function get($className)
    {
        if (isset($this->instances[$className])) {
            return $this->instances[$className];
        }

        $reflector = new \ReflectionClass($className);

        if (!$reflector->isInstantiable()) {
            throw new \Exception("Класс {$className} не может быть создан.");
        }

        $constructor = $reflector->getConstructor();

        if ($constructor === null) {
            $instance = new $className();
        } else {
            $constructorParams = $constructor->getParameters();
            $necessaryParams = [];

            foreach ($constructorParams as $param) {
                $type = $param->getType();

                if ($type && (!$type->isBuiltin())) {
                    $paramName = $type->getName();
                    $necessaryParams[] = $this->get($paramName);
                } else {
                    throw new \Exception("Параметр {$param->getName()} в {$className} без типа или примитив.");
                }
            }
            $instance = $reflector->newInstanceArgs($necessaryParams);
        }
        $this->instances[$className] = $instance;

        return $instance;
    }
}
