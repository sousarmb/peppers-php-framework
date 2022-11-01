<?php

namespace Peppers\Contracts\DataValidation;

use Peppers\Contracts\DataValidation\Validate;

interface RequestValidator extends Validate {

    /**
     * 
     * @return array
     */
    public function failed(): array;

    /**
     * 
     * @return array
     */
    public function passed(): array;
}
