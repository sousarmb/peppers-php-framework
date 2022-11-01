<?php

namespace Peppers\Helpers\DataValidation;

use DateTime;
use RuntimeException;
use Peppers\Base\Validator;
use Peppers\Contracts\DataValidation\Range;
use Peppers\Helpers\Dates;

class IsDate extends Validator implements Range {

    private DateTime $_startValue;
    private DateTime $_endValue;
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
     * @param int|float|string $startValue
     * @param int|float|string $endValue
     * @return self
     */
    public function between(
            int|float|string $startValue,
            int|float|string $endValue
    ): self {
        $this->_startValue = Dates::getDateFromScalar($startValue);
        $this->_endValue = Dates::getDateFromScalar($endValue);
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
        $this->_startValue = $this->getDateFromScalar($startValue);
        $this->_endValue = $this->getDateFromScalar($endValue);
        $this->_betweenOrNot = true;
        return $this;
    }

    /**
     * 
     * @param int|float|string $value
     * @return self
     */
    public function setMin(int|float|string $value): self {
        $this->_startValue = Dates::getDateFromScalar($value);
        return $this;
    }

    /**
     * 
     * @param int|float|string $value
     * @return self
     */
    public function setMax(int|float|string $value): self {
        $this->_endValue = Dates::getDateFromScalar($value);
        return $this;
    }

    /**
     * @return bool
     */
    public function validate(): bool {
        $this->hasRun = true;
        try {
            $requestDate = Dates::getDateFromScalar($this->value);
        } catch (RuntimeException $e) {
            $this->reason = $e->getMessage();
            return $this->isValid = false;
        }
        if (isset($this->_betweenOrNot)) {
            if ($this->_betweenOrNot) {
                // between dates
                if (($this->_startValue > $requestDate) || ($requestDate > $this->_endValue)) {
                    $this->reason = 'Date not between validation dates';
                    return $this->isValid = false;
                }
            } else {
                // not between dates
                if (($this->_startValue <= $requestDate) && ($requestDate <= $this->_endValue)) {
                    $this->reason = 'Date between validation dates';
                    return $this->isValid = false;
                }
            }
        }
        if (isset($this->startValue)) {
            if ($requestDate < $this->_startValue) {
                $this->reason = 'Request date before validation date';
                return $this->isValid = false;
            }
        }
        if (isset($this->endValue)) {
            if ($requestDate > $this->_endValue) {
                $this->reason = 'Request date after validation date';
                return $this->isValid = false;
            }
        }

        return $this->isValid = true;
    }

}
