<?php

namespace Peppers\Exceptions;

use Exception;
use Peppers\Contracts\DefaultMethod;

class MethodNotAllowed extends Exception implements DefaultMethod {

    private array $_allowedMethods = [
        'DELETE',
        'GET',
        'HEAD',
        'POST'
    ];

    /**
     *
     * @param string $typeToSend
     * @param array $acceptedTypes
     */
    public function __construct() {
        parent::__construct('Method not allowed', 405);
    }

    /**
     * Use this method to perform actions when the exception is (finally) 
     * caught by the kernel
     * 
     * @return mixed
     */
    public function default(): mixed {
        header('Allowed: ' . implode(', ', $this->_allowedMethods));
        return null;
    }

}
