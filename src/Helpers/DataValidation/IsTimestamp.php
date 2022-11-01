<?php

namespace Peppers\Helpers\DataValidation;

use Peppers\Helpers\DataValidation\IsInteger;

class IsTimestamp extends IsInteger {

    /**
     * 
     * @param mixed $value
     */
    public function __construct(mixed $value = null) {
        parent::__construct($value);
    }

    /**
     * 
     * @return bool
     */
    public function validate(): bool {
        $this->hasRun = true;
        if (!parent::validate()) {
            $this->reason = 'Invalid timestamp';
            return $this->isValid = false;
        }
        if ($this->value < 0) {
            $this->reason = 'Invalid timestamp';
            return $this->isValid = false;
        }

        $dateObj = (new DateTime())->setTimestamp($this->value);
        if ($dateObj instanceof DateTime) {
            return $this->isValid = true;
        }

        $this->reason = 'Invalid timestamp';
        return $this->is_valid = false;
    }

}
