<?php

namespace Peppers\Exceptions;

use Exception;
use Peppers\Contracts\DefaultMethod;

class InvalidRouteHandler extends Exception implements DefaultMethod {

    /**
     *
     * @param int $key
     * @param string $unexpectedType
     */
    public function __construct(
            int $key,
            string $unexpectedType
    ) {
        $message = sprintf(
                'Expecting array or string or Closure instance for route handler at %s position but got %s',
                $key,
                $unexpectedType
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
