<?php
namespace App\Core;

class DIContainer {
    public function __construct() {}
    public function get($className) {

        $reflector = new \ReflectionClass($className);

        $constructor = $reflector->getConstructor();

        if ($constructor === null) {
            return new $className();
        }

        $constructorParams = $constructor->getParameters();

        $necessaryParams = [];

        foreach($constructorParams as $param) {
            $type = $param->getType();

            if ($type && (!$type->isBuiltin())) {
                $paramName = $type->getName();
                $necessaryParams[] = $this->get($paramName);
            }
            else {
                throw new \Exception("Параметр без типа или типа значения");
            }
        
        }
        return $reflector->newInstanceArgs($necessaryParams);

    }
}
