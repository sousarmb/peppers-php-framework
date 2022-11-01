<?php

namespace Peppers\Exceptions;

use Exception;
use Peppers\Contracts\DataValidation\Validate;

class InterruptValidation extends Exception {

    protected Validate $parameterOrValidator;

    /**
     * 
     * @param Validate $parameterOrValidator
     */
    public function __construct(Validate $parameterOrValidator) {
        $this->parameterOrValidator = $parameterOrValidator;
        parent::__construct('Interrupted');
    }

    /**
     * 
     * @return Validate
     */
    public function getFault(): Validate {
        return $this->parameterOrValidator;
    }

}
