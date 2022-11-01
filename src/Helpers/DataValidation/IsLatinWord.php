<?php

namespace Peppers\Helpers\DataValidation;

use Peppers\Base\BlackOrWhiteValidator;
use Peppers\Contracts\DataValidation\Length;

class IsLatinWord extends BlackOrWhiteValidator implements Length {

    private int $_exactLength;
    private int $_maxLength;
    private int $_minLength;
    private string $_pattern = '/^\p{Latin}+$/iu';

    /**
     * 
     * @param mixed $value
     */
    public function __construct(mixed $value = null) {
        $this->value = $value;
    }

    /**
     * 
     * @param int $value
     * @return self
     */
    public function setExactLength(int $value): self {
        $this->_exactLength = $value;
        return $this;
    }

    /**
     * 
     * @param int $value
     * @return self
     */
    public function setMaxLength(int $value): self {
        $this->_maxLength = $value;
        return $this;
    }

    /**
     * 
     * @param int $value
     * @return self
     */
    public function setMinLength(int $value): self {
        $this->_minLength = $value;
        return $this;
    }

    /**
     * @return bool
     */
    public function validate(): bool {
        $this->hasRun = true;
        $regEx = new RegularExpression($this->_pattern, $this->value);
        if ($regEx->validate()) {
            $this->reason = 'Invalid word';
            return $this->isValid = false;
        }

        $length = mb_strlen($this->value);
        if (isset($this->_exactLength)) {
            if ($length !== $this->_exactLength) {
                $this->reason = 'Invalid length';
                return $this->isValid = false;
            }
        }
        if (isset($this->blackList)) {
            if ($this->isValueBlackListed()) {
                $this->reason = 'Invalid word';
                return $this->isValid = false;
            }
        }
        if (isset($this->whiteList)) {
            if (!$this->isValueWhiteListed()) {
                $this->reason = 'Invalid word';
                return $this->isValid = false;
            }
        }
        if (isset($this->_maxLength)) {
            if ($length > $this->_maxLength) {
                $this->reason = 'Longer than allowed';
                return $this->isValid = false;
            }
        }
        if (isset($this->_minLength)) {
            if ($length < $this->_minLength) {
                $this->reason = 'Shorter than allowed';
                return $this->isValid = false;
            }
        }

        return $this->isValid = true;
    }

}
