<?php

namespace Peppers\Helpers\DataValidation;

use Peppers\Base\BlackOrWhiteValidator;

class IsIpAddress extends BlackOrWhiteValidator {

    private bool $_v4OrV6 = false;
    private bool $_privateRangeOrNot = false;
    private bool $_reservedRangeOrNot = false;

    /**
     * 
     * @param mixed $value
     */
    public function __construct(mixed $value = null) {
        $this->value = $value;
    }

    /**
     * 
     * @param bool $noYes
     * @return self
     */
    public function allowPrivateRange(bool $noYes = false): self {
        $this->_privateRangeOrNot = $noYes;
        return $this;
    }

    /**
     * 
     * @param bool $noYes
     * @return self
     */
    public function allowReservedRange(bool $noYes = false): self {
        $this->_reservedRangeOrNot = $noYes;
        return $this;
    }

    /**
     * 
     * @param bool $v4OrV6
     * @return self
     */
    public function setV4OrV6(bool $v4OrV6 = false): self {
        $this->_v4OrV6 = $v4OrV6;
        return $this;
    }

    /**
     * @return bool
     */
    public function validate(): bool {
        $this->hasRun = true;
        if ($this->_v4OrV6) {
            $flags = FILTER_FLAG_IPV6;
        } else {
            $flags = FILTER_FLAG_IPV4;
        }
        if ($this->_privateRangeOrNot) {
            $flags |= FILTER_FLAG_NO_PRIV_RANGE;
        }
        if ($this->_reservedRangeOrNot) {
            $flags |= FILTER_FLAG_NO_RES_RANGE;
        }
        $filter = filter_var(
                $this->value,
                FILTER_VALIDATE_IP,
                ['flags' => $flags]
        );
        if ($filter === false) {
            $this->reason = 'Invalid IP address';
            return $this->isValid = false;
        }
        if (isset($this->blackList)) {
            if ($this->isValueBlackListed()) {
                $this->reason = 'Invalid IP address';
                return $this->isValid = false;
            }
        }
        if (isset($this->whiteList)) {
            if (!$this->isValueWhiteListed()) {
                $this->reason = 'Invalid IP address';
                return $this->isValid = false;
            }
        }

        return $this->isValid = true;
    }

}
