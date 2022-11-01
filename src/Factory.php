<?php

namespace Peppers;

use Closure;
use Peppers\Contracts\Factory as iFactory;
use Peppers\Helpers\Http\Request\BodyParameter;
use Peppers\Helpers\Http\Request\FileParameter;
use Peppers\Helpers\Http\Request\QueryParameter;
use Peppers\Helpers\Http\Request\PathParameter;
use Peppers\Helpers\Service\BoundTo;
use Peppers\ServiceLocator;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;

abstract class Factory implements iFactory {

    private static array $scalarOrNot = [
        'array',
        'bool',
        'float',
        'int',
        'null',
        'string'
    ];

    /**
     *
     * @param Closure|string $implementation
     * @param array $parameters
     * @param string $boundTo
     * @return object|array
     * @throws ReflectionException
     */
    public static function getClassInstance(
            Closure|string $implementation,
            array $parameters = [],
            string $boundTo = ''
    ): array|object {
        $isClosure = $implementation instanceof Closure;
        $reflection = $isClosure ? new ReflectionFunction($implementation) : new ReflectionClass($implementation);
        if ($isClosure) {
            if ($parameters) {
                return $reflection->invokeArgs($parameters);
            }

            $parameters = $reflection->getParameters();
            if ($parameters) {
                $parameters = static::getParametersValue(
                                $parameters,
                                $boundTo
                );
            }

            return $reflection->invokeArgs($parameters);
        }
        if (!$reflection->isInstantiable()) {
            throw new ReflectionException("$implementation is not instantiable");
        }
        if ($parameters) {
            return $reflection->newInstanceArgs($parameters);
        }

        $classConstructor = $reflection->getConstructor();
        if ($classConstructor) {
            $parameters = static::getParametersValue(
                            $classConstructor->getParameters(),
                            $implementation
            );
        }

        return $reflection->newInstanceArgs($parameters);
    }

    /**
     * Note that method dependencies must be bound to the method's class
     * in the services file!
     * 
     * @param string $methodName
     * @param string $implementation
     * @return array
     * @throws ReflectionException
     */
    public static function getMethodInstance(
            string $methodName,
            string $implementation
    ): array {
        $classInstance = static::getClassInstance($implementation);
        $methodInstance = new ReflectionMethod(
                $classInstance,
                $methodName
        );
        if (!$methodInstance->isPublic()) {
            throw new ReflectionException("$implementation::$methodName() must be public");
        }

        return [
            $methodInstance,
            $classInstance,
            static::getParametersValue(
                    $methodInstance->getParameters(),
                    $implementation
            )
        ];
    }

    /**
     *
     * @param array $parameters
     * @param string $boundTo
     * @return array
     */
    private static function getParametersValue(
            array $parameters,
            string $boundTo = ''
    ): array {
        $values = [];
        if (!$parameters) {
            return $values;
        }

        foreach ($parameters as $parameter) {
            $type = (string) $parameter->getType();
            if (in_array($type, self::$scalarOrNot)) {
                $values[] = $parameter->isDefaultValueAvailable() === true ? $parameter->getDefaultValue() : null;
                continue;
            }

            switch ($type) {
                case BodyParameter::class:
                case QueryParameter::class:
                case PathParameter::class:
                case FileParameter::class:
                    $values[] = new $type(
                            $parameter->getName(),
                            $parameter->isDefaultValueAvailable() === true ? $parameter->getDefaultValue() : null
                    );
                    break;

                case BoundTo::class:
                    $values[] = new BoundTo($boundTo);
                    break;

                default:
                    static::getDependencyForInjection(
                            $type,
                            $values,
                            $boundTo
                    );
                    break;
            }
        }

        return $values;
    }

    /**
     *
     * @param string $implementation
     * @param array $values
     * @param string $boundTo
     * @return void
     */
    private static function getDependencyForInjection(
            string $implementation,
            array &$values,
            string $boundTo = ''
    ): void {
        if (strlen($boundTo) && ServiceLocator::hasBound($implementation, $boundTo)) {
            $values[] = ServiceLocator::getBound($implementation, $boundTo);
        } elseif (ServiceLocator::has($implementation)) {
            $values[] = ServiceLocator::get($implementation);
        } else {
            $values[] = static::getClassInstance($implementation);
        }
    }

}
