<?php

namespace Peppers\Contracts;

interface Factory {

    /**
     * 
     * @param string $implementation
     * @return array|object
     */
    public static function getClassInstance(string $implementation): array|object;

    /**
     * 
     * @param string $methodName
     * @param string $implementation
     * @return array
     */
    public static function getMethodInstance(
            string $methodName,
            string $implementation
    ): array;
}
