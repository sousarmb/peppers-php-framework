<?php

namespace Peppers\Helpers\DataValidation;

use Peppers\Base\BlackOrWhiteValidator;
use Peppers\Contracts\DataValidation\Length;

class IsEmail extends BlackOrWhiteValidator implements Length {

    private $exactLength;
    private $maxLength;
    private $minLength;

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
        $this->exactLength = $value;
        return $this;
    }

    /**
     * 
     * @param int $value
     * @return self
     */
    public function setMaxLength(int $value): self {
        $this->maxLength = $value;
        return $this;
    }

    /**
     * 
     * @param int $value
     * @return self
     */
    public function setMinLength(int $value): self {
        $this->minLength = $value;
        return $this;
    }

    /**
     * @return bool
     */
    public function validate(): bool {
        $this->hasRun = true;
        $filter = filter_var($this->value, FILTER_VALIDATE_EMAIL);
        if ($filter === false) {
            $this->reason = 'Invalid email address';
            return $this->isValid = false;
        }
        if (isset($this->blackList)) {
            if ($this->isValueBlackListed()) {
                $this->reason = 'Invalid email address';
                return $this->isValid = false;
            }
        }
        if (isset($this->whiteList)) {
            if (!$this->isValueWhiteListed()) {
                $this->reason = 'Invalid email address';
                return $this->isValid = false;
            }
        }
        if (isset($this->exactLength)) {
            if (strlen($this->value) !== $this->exactLength) {
                $this->reason = 'Invalid length';
                return $this->isValid = false;
            }
        }
        if (isset($this->maxLength)) {
            if (strlen($this->value) !== $this->maxLength) {
                $this->reason = 'Invalid length';
                return $this->isValid = false;
            }
        }
        if (isset($this->minLength)) {
            if (strlen($this->value) !== $this->minLength) {
                $this->reason = 'Invalid length';
                return $this->isValid = false;
            }
        }

        return $this->isValid = true;
    }

}
