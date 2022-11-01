<?php

namespace Peppers\Helpers\DataValidation;

use Peppers\Base\Validator;
use Peppers\Contracts\DataValidation\Length;

class IsArray extends Validator implements Length {

    private int $_exactLength;
    private array $_expectKeys;
    private int $_maxLength;
    private int $_minLength;

    /**
     * 
     * @param mixed $value
     */
    public function __construct(mixed $value = null) {
        $this->value = $value;
    }

    /**
     * 
     * @param array $parameters
     * @return self
     */
    public function expectKeys(array $parameters): self {
        $this->_expectKeys = $parameters;
        return $this;
    }

    /**
     * 
     * @param int $length
     * @return self
     */
    public function setExactLength(int $length): self {
        $this->_exactLength = $length;
        return $this;
    }

    /**
     * 
     * @param int $length
     * @return self
     */
    public function setMaxLength(int $length): self {
        $this->_maxLength = $length;
        return $this;
    }

    /**
     * 
     * @param type $length
     * @return self
     */
    public function setMinLength($length): self {
        $this->_minLength = $length;
        return $this;
    }

    /**
     * 
     * @return bool
     */
    public function validate(): bool {
        $this->hasRun = true;
        if (!is_array($this->value)) {
            $this->reason = 'Not array';
            return $this->isValid = false;
        }

        $length = count($this->value);
        if (isset($this->_exactLength)) {
            if ($length != $this->exactLength) {
                $this->reason = 'Length differs from expected';
                return $this->isValid = false;
            }
        }
        if (isset($this->_maxLength)) {
            if ($length > $this->_maxLength) {
                $this->reason = 'Maximum length fail';
                return $this->isValid = false;
            }
        }
        if (isset($this->_minLength)) {
            if ($length < $this->_minLength) {
                $this->reason = 'Minimum length fail';
                return $this->isValid = false;
            }
        }
        if (isset($this->_expectKeys)) {
            $keys = array_keys($this->value);
            if (($diff = array_diff($this->_expectKeys, $keys)) !== []) {
                $this->reason = 'Missing key(s): ' . implode(', ', $diff);
                return $this->isValid = false;
            }
        }

        return $this->isValid = true;
    }

}
