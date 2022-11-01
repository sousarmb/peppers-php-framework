<?php

namespace Peppers\Exceptions;

use Exception;
use Peppers\Contracts\DefaultMethod;

class ResourceNotFound extends Exception implements DefaultMethod {

    /**
     * 
     * @param string $name
     */
    public function __construct(string $name = '') {
        $message = 'Resource not found';
        if (strlen($name)) {
            $message .= " $name";
        }

        parent::__construct($message, 404);
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
