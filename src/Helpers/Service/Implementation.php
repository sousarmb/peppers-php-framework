<?php

namespace Peppers\Helpers\Service;

use Peppers\Helpers\Service\AbstractImplementation;
use Peppers\Helpers\Service\ConcreteImplementation;

final class Implementation {

    /**
     *
     * @param string $implementation
     * @param array $bindings
     * @return AbstractImplementation
     */
    public static function abstract(
            string $implementation,
            array $bindings = []
    ): AbstractImplementation {
        return new AbstractImplementation(
                $implementation,
                $bindings
        );
    }

    /**
     *
     * @param string $implementation
     * @return ConcreteImplementation
     */
    public static function concrete(string $implementation): ConcreteImplementation {
        return new ConcreteImplementation($implementation);
    }

}
