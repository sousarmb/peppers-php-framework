<?php

namespace Peppers\Helpers\DataValidation;

use Peppers\Base\BlackOrWhiteValidator;
use Peppers\Contracts\DataValidation\Range;

class IsFloat extends BlackOrWhiteValidator implements Range {

    private float $_startValue;
    private float $_endValue;
    private bool $_betweenOrNot;

    /**
     * 
     * @param mixed $value
     */
    public function __construct(mixed $value = null) {
        $this->value = $value;
    }

    /**
     * 
     * @param int|float|string $value
     * @return self
     */
    public function setMin(int|float|string $value): self {
        $this->_startValue = $value;
        return $this;
    }

    /**
     * 
     * @param int|float|string $value
     * @return self
     */
    public function setMax(int|float|string $value): self {
        $this->_endValue = $value;
        return $this;
    }

    /**
     * 
     * @param int|float|string $startValue
     * @param int|float|string $endValue
     * @return self
     */
    public function between(
            int|float|string $startValue,
            int|float|string $endValue
    ): self {
        $this->_startValue = $startValue;
        $this->_endValue = $endValue;
        $this->_betweenOrNot = false;
        return $this;
    }

    /**
     * 
     * @param int|float|string $startValue
     * @param int|float|string $endValue
     * @return self
     */
    public function notBetween(
            int|float|string $startValue,
            int|float|string $endValue
    ): self {
        $this->_startValue = $startValue;
        $this->_endValue = $endValue;
        $this->_betweenOrNot = true;
        return $this;
    }

    /**
     * @return bool
     */
    public function validate(): bool {
        $this->hasRun = true;
        $filter = filter_var($this->value, FILTER_VALIDATE_FLOAT);
        if ($filter === false) {
            $this->reason = 'Not valid float value';
            return $this->isValid = false;
        }
        if (isset($this->_betweenOrNot)) {
            if ($this->_betweenOrNot) {
                // between
                if (($this->_startValue > $this->value) || ($this->value > $this->_endValue)) {
                    $this->reason = 'Value not between validation floats';
                    return $this->isValid = false;
                }
            } else {
                // not between
                if (($this->_startValue <= $this->value) && ($this->value <= $this->_endValue)) {
                    $this->reason = 'Value between validation floats';
                    return $this->isValid = false;
                }
            }
        }
        if (isset($this->blackList)) {
            if ($this->isValueBlackListed()) {
                $this->reason = 'Value not allowed';
                return $this->isValid = false;
            }
        }
        if (isset($this->whiteList)) {
            if (!$this->isValueWhiteListed()) {
                $this->reason = 'Value not allowed';
                return $this->isValid = false;
            }
        }
        if (isset($this->startValue)) {
            if ($this->value < $this->_startValue) {
                $this->reason = 'Value less than allowed';
                return $this->isValid = false;
            }
        }
        if (isset($this->endValue)) {
            if ($this->value < $this->_startValue) {
                $this->reason = 'Value bigger than allowed';
                return $this->isValid = false;
            }
        }

        return $this->isValid = true;
    }

}
