<?php

namespace Peppers\Exceptions;

use Exception;
use Peppers\Contracts\DefaultMethod;

class BadRequest extends Exception implements DefaultMethod {

    public function __construct() {
        parent::__construct('Bad Request', 400);
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
