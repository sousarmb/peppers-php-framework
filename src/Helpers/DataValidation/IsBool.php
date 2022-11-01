<?php

namespace Peppers\Helpers\DataValidation;

use Peppers\Base\Validator;

class IsBool extends Validator {

    /**
     * 
     * @param mixed $value
     */
    public function __construct(mixed $value = null) {
        $this->value = $value;
    }

    /**
     * @return bool
     */
    public function validate(): bool {
        $this->hasRun = true;
        switch ($this->value) {
            case '0':
            case 'false':
            case 'off':
            case 'no':
            case '1':
            case 'true':
            case 'on':
            case 'yes':
                return $this->isValid = true;
            default:
                $this->reason = 'Not boolean';
                return $this->isValid = false;
        }
    }

}
