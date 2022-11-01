<?php

namespace Peppers\Helpers\DataValidation;

use Peppers\Base\Validator;
use Peppers\Contracts\DataValidation\Length;

class IsLatinText extends Validator implements Length {

    private array $_blackList;
    private int $_exactLength;
    private bool $_forbidden;
    private int $_maxLength;
    private int $_minLength;
    private string $_pattern = '/[^\p{P}\p{Latin}\s0-9]/iu';

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
     * 
     * @param array $values
     * @return self
     */
    public function setForbidden(array $values): self {
        $this->_blackList = $values;
        return $this;
    }

    /**
     * 
     * @return bool
     */
    public function isValueBlackListed(): bool {
        $patternWords = '~' . implode('|', $this->_blackList) . '~iu';
        if ((new RegularExpression($patternWords, $this->value))->validate()) {
            return $this->_forbidden = true;
        }

        return $this->_forbidden = false;
    }

    /**
     * @return bool
     */
    public function validate(): bool {
        $this->hasRun = true;
        $regEx = new RegularExpression($this->_pattern, $this->value);
        if ($regEx->validate()) {
            $this->reason = 'Invalid text';
            return $this->isValid = false;
        }

        $length = mb_strlen($this->value);
        if (isset($this->exactLength)) {
            if ($length !== $this->_exactLength) {
                $this->reason = 'Invalid length';
                return $this->isValid = false;
            }
        }
        if (isset($this->blackList)) {
            if ($this->isValueBlackListed()) {
                $this->reason = 'Black listed words found';
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
