<?php

namespace Peppers\Contracts;

interface Locator {

    /**
     * 
     * @param array $serviceDescriptors
     * @return void
     */
    public static function boot(array $serviceDescriptors): void;

    /**
     * 
     * @param string $implementation
     * @param array $with
     * @return object
     */
    public static function get(
            string $implementation,
            array $with = []
    ): object;

    /**
     * 
     * @param string $implementation
     * @return bool
     */
    public static function has(string $implementation): bool;
}
