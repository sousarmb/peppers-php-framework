<?php

namespace Peppers\Helpers\DataValidation;

use Peppers\Base\BlackOrWhiteValidator;

class IsMacAddress extends BlackOrWhiteValidator {

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
        $filter = filter_var($this->value, FILTER_VALIDATE_MAC);
        if ($filter === false) {
            $this->reason = 'Invalid MAC address';
            return $this->isValid = false;
        }
        if (isset($this->blackList)) {
            if ($this->isValueBlackListed()) {
                $this->reason = 'Invalid MAC address';
                return $this->isValid = false;
            }
        }
        if (isset($this->whiteList)) {
            if (!$this->isValueWhiteListed()) {
                $this->reason = 'Invalid MAC address';
                return $this->isValid = false;
            }
        }

        return $this->isValid = true;
    }

}
