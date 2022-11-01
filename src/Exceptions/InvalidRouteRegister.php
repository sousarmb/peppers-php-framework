<?php

namespace Peppers\Exceptions;

use Exception;
use Peppers\Contracts\DefaultMethod;

class InvalidRouteRegister extends Exception implements DefaultMethod {

    /**
     *
     * @param int $key
     * @param array|string $expectedType
     */
    public function __construct(
            int $key,
            array|string $expectedType
    ) {
        $message = sprintf(
                'Invalid route register at %s position, must be instance of: %s',
                $key,
                is_array($expectedType) ? implode('|', $expectedType) : $expectedType
        );
        parent::__construct($message, 500);
    }

    /**
     * Use this method to perform actions when the exception is (finally) 
     * caught by the kernel
     *
     * @return mixed
     */
    public function default(): mixed {
        return null;
    }

}
