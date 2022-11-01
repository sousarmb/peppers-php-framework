<?php

namespace Peppers;

use Generator;
use Peppers\Base\Strategy;
use Peppers\Contracts\Locator;
use Peppers\Contracts\Services\AbstractImplementation;
use Peppers\Contracts\Services\ConcreteImplementation;
use Peppers\Contracts\Services\Descriptor;
use Peppers\Exceptions\StrategyFail;
use Peppers\Factory;
use RuntimeException;

abstract class ServiceLocator implements Locator {

    private static array $_allServiceInstances = [];
    private static bool $_booted = false;
    private static array $_indexBound = [];
    private static array $_indexUnbound = [];
    private static array $_serviceDescriptors = [];
    private static array $_singletons = [];

    /**
     *
     * @param array $serviceDescriptors
     * @return void
     * @throws RuntimeException
     */
    public static function boot(
            array $serviceDescriptors
    ): void {
        if (static::$_booted) {
            throw new RuntimeException(__CLASS__ . ' already booted');
        }

        foreach ($serviceDescriptors as $key => $serviceDescriptor) {
            static::buildDescriptorIndex($serviceDescriptor, $key);
        }
        static::$_serviceDescriptors = $serviceDescriptors;
        foreach (static::$_indexUnbound as $index) {
            $descriptor = static::$_serviceDescriptors[$index];
            if (!($descriptor instanceof ConcreteImplementation)) {
                continue;
            }
            if (!$descriptor->getIsLazyLoad() && $descriptor->getIsSingleton()) {
                static::$_singletons[$index] = Factory::getClassInstance($descriptor->getImplementation());
                $descriptor->setSingletonLoaded();
            }
        }

        static::$_booted = true;
    }

    /**
     *
     * @param Descriptor $service
     * @param int $key
     * @return void
     */
    private static function buildDescriptorIndex(
            Descriptor $service,
            int $key
    ): void {
        $implementation = $service->getImplementation();
        if ($service instanceof AbstractImplementation) {
            if ($service->hasBindings()) {
                static::setupIndexBound($implementation);
                static::$_indexBound[$implementation][$key] = $service->getBindings();
            } else {
                static::$_indexUnbound[$implementation] = $key;
            }
        } else {
            static::$_indexUnbound[$implementation] = $key;
        }
    }

    /**
     * 
     * @param string $implementation    Class name that you want returned as 
     *                                  service, as defined in services.php 
     *                                  file
     * @param array $with               Passed to the instance __construct()
     * @return object                   Service|StrategyFail instance
     * @throws RuntimeException
     * @throws StrategyFail
     */
    public static function get(
            string $implementation,
            array $with = []
    ): object {
        if (!static::has($implementation)) {
            $msg = sprintf(
                    'Trying to get unregistered service %s',
                    $implementation
            );
            throw new RuntimeException($msg);
        }

        $index = static::$_indexUnbound[$implementation];
        $descriptor = static::$_serviceDescriptors[$index];
        $abstractOrConcrete = $descriptor instanceof AbstractImplementation ? $descriptor->getProvider() : $implementation;
        if ($descriptor->getIsSingletonLoaded()) {
            return static::$_singletons[$index];
        }
        // make new service instance
        $serviceOrStrategy = Factory::getClassInstance(
                        $abstractOrConcrete,
                        $with
        );
        if ($serviceOrStrategy instanceof Strategy) {
            /* looks like we got a Strategy instance instead of the service so, 
             * run the strategy and hope it returns a valid instance of the 
             * requested service */
            $service = $serviceOrStrategy->default();
            if (!($service instanceof $implementation)) {
                $strategyFail = new StrategyFail($serviceOrStrategy::class);
                if ($serviceOrStrategy->allowedToFail()) {
                    return $strategyFail;
                }

                throw $strategyFail;
            }
        } else {
            // NULL it because the service instance is in $serviceOrStrategy
            $service = null;
        }
        if ($descriptor->getIsSingleton()) {
            $descriptor->setSingletonLoaded();
            // store for future use
            static::$_singletons[$index] = $service ?? $serviceOrStrategy;
        }
        // store object id so it may be shudown properly in the process
        static::$_allServiceInstances[] = $service ?? $serviceOrStrategy;
        // return service instance
        return $service ?? $serviceOrStrategy;
    }

    /**
     *
     * @param string $implementation
     * @param string $boundTo
     * @param array $with
     * @return object
     * @throws RuntimeException
     */
    public static function getBound(
            string $implementation,
            string $boundTo,
            array $with = []
    ): object {
        if (!static::hasBound($implementation, $boundTo)) {
            $msg = sprintf(
                    'Trying to get unregistered bound service %s',
                    $implementation
            );
            throw new RuntimeException($msg);
        }

        $current = reset(static::$_indexBound[$implementation]);
        do {
            if (in_array($boundTo, $current)) {
                $index = key(static::$_indexBound[$implementation]);
                end(static::$_indexBound[$implementation]);
            }
        } while ($current = next(static::$_indexBound[$implementation]));
        $descriptor = static::$_serviceDescriptors[$index];
        if ($descriptor->getIsSingletonLoaded()) {
            return static::$_singletons[$index];
        }
        // make new service instance
        $service = Factory::getClassInstance(
                        $descriptor->getProvider(),
                        $with,
                        $boundTo
        );
        if ($descriptor->getIsSingleton()) {
            $descriptor->setSingletonLoaded();
            // store for future use
            static::$_singletons[$index] = $service;
        }
        // store object id so it may be shudown properly in the process
        static::$_allServiceInstances[] = $service ?? $service;
        // return new service instance
        return $service;
    }

    /**
     *
     * @param string $implementation
     * @return bool
     */
    public static function has(
            string $implementation
    ): bool {
        return array_key_exists($implementation, static::$_indexUnbound);
    }

    /**
     *
     * @param string $implementation
     * @param string $boundTo
     * @return bool
     */
    public static function hasBound(
            string $implementation,
            string $boundTo
    ): bool {
        if (array_key_exists($implementation, static::$_indexBound)) {
            foreach (static::$_indexBound[$implementation] as $bindings) {
                if (in_array($boundTo, $bindings)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     *
     * @param string $implementation
     * @return void
     */
    private static function setupIndexBound(
            string $implementation
    ): void {
        if (!array_key_exists($implementation, static::$_indexBound)) {
            static::$_indexBound[$implementation] = [];
        }
    }

    /**
     * Allows iteration over all service instances ever returned by the
     * ServiceLocator, be they singleton or not.
     * 
     * Note: This should only be used in the framework shutdown process 
     * 
     * @return Generator
     */
    public static function getAllServices(): Generator {
        foreach (static::$_allServiceInstances as $service) {
            yield $service;
        }
    }

}
