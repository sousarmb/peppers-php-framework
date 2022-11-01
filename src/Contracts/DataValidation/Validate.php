<?php

namespace Peppers\Contracts\DataValidation;

interface Validate {

    /**
     * 
     * @return bool
     */
    public function hasRun(): bool;

    /**
     * 
     * @return bool
     */
    public function validate(): bool;
}
