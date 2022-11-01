<?php

namespace Peppers\Helpers\DataValidation;

use RuntimeException;
use Peppers\Base\RequestParameter;
use Peppers\Base\Validator;
use Peppers\Contracts\DataValidation\Validate;
use Peppers\Helpers\Types\Operator;

class IsRequired extends Validator {

    private array $_if;
    private array $_with;
    private array $_without;

    /**
     * 
     * @param mixed $value
     */
    public function __construct(mixed $value = null) {
        $this->value = $value;
    }

    /**
     * 
     * @param type $otherParameter
     * @param type $operator
     * @param type $value
     * @return bool
     */
    private function evalIfString(
            $otherParameter,
            $operator,
            $value
    ): bool {
        if (!is_string($otherParameter->getValue())) {
            return false;
        }

        switch ($operator) {
            case $operator::eq:
                return $otherParameter->getValue() == $value;
            case $operator::gt:
                return strlen($otherParameter->getValue()) > strlen($value);
            case $operator::gte:
                return strlen($otherParameter->getValue()) >= strlen($value);
            case $operator::lt:
                return strlen($otherParameter->getValue()) < strlen($value);
            case $operator::lte:
                return strlen($otherParameter->getValue()) <= strlen($value);
            case $operator::neq:
            default:
                return $otherParameter->getValue() != $value;
        }
    }

    /**
     * 
     * @param type $otherParameter
     * @param type $operator
     * @param type $value
     * @return array
     */
    private function evalIfFloat($otherParameter,
            $operator,
            $value
    ): bool {
        $validator = new IsFloat($otherParameter->getValue());
        if (!$validator->validate()) {
            return false;
        }

        switch ($operator) {
            case $operator::eq:
                return $otherParameter->getValue() == $value;
            case $operator::gt:
                return $otherParameter->getValue() > $value;
            case $operator::gte:
                return $otherParameter->getValue() >= $value;
            case $operator::lt:
                return $otherParameter->getValue() < $value;
            case $operator::lte:
                return $otherParameter->getValue() <= $value;
            case $operator::neq:
            default:
                return $otherParameter->getValue() != $value;
        }
    }

    /**
     * 
     * @param type $otherParameter
     * @param type $operator
     * @param type $value
     * @return bool
     */
    private function evalIfInt(
            $otherParameter,
            $operator,
            $value
    ): bool {
        $validator = new IsInteger($otherParameter->getValue());
        if (!$validator->validate()) {
            return false;
        }

        switch ($operator) {
            case $operator::eq:
                return $otherParameter->getValue() == $value;
            case $operator::gt:
                return $otherParameter->getValue() > $value;
            case $operator::gte:
                return $otherParameter->getValue() >= $value;
            case $operator::lt:
                return $otherParameter->getValue() < $value;
            case $operator::lte:
                return $otherParameter->getValue() <= $value;
            case $operator::neq:
            default:
                return $otherParameter->getValue() != $value;
        }
    }

    /**
     * 
     * @param type $otherParameter
     * @param type $operator
     * @param type $value
     * @return array
     */
    private function evalIfBool(
            $otherParameter,
            $operator,
            $value
    ): bool {
        $validator = new IsBool($otherParameter->getValue());
        if (!$validator->validate()) {
            return false;
        }
        // just because bool in a PHP HTTP request may assume "many faces"
        $otherParameterValue = boolval($otherParameter->getValue());
        switch ($operator) {
            case $operator::eq:
                return $otherParameterValue == $value;

            case $operator::neq:
                return $otherParameterValue != $value;

            default:
                throw new RuntimeException('Invalid operator');
        }
    }

    /**
     * 
     * @param RequestParameter|Validate $otherParameter
     * @param Operator $operator
     * @param bool|int|float|string $value
     * @return self
     */
    public function setIf(
            RequestParameter|Validate $otherParameter,
            Operator $operator,
            bool|int|float|string $value,
    ): self {
        if ($value instanceof Validate) {
            $this->_if[] = $otherParameter;
        } else {
            $this->_if[] = [$otherParameter, $operator, $value];
        }

        return $this;
    }

    /**
     * This parameter is required if $otherParameter is present in the request.
     *
     * $otherParameter hints at where the validator must search for the value: 
     * request query, body or files.
     * 
     * @param RequestParameter $otherParameter
     * @return self
     */
    public function setWith(RequestParameter $otherParameter): self {
        $this->_with[] = $otherParameter;
        return $this;
    }

    /**
     * This parameter is required if $otherParameter is not present in the 
     * request.
     *
     * $otherParameter hints at where the validator must search for the value: 
     * request query, body or files.
     * 
     * @param RequestParameter $otherParameter
     * @return self
     */
    public function setWithout(RequestParameter $otherParameter): self {
        $this->_without[] = $otherParameter;
        return $this;
    }

    /**
     * @return bool
     */
    public function validate(): bool {
        $this->hasRun = true;
        $valueIsNull = is_null($this->value);
        if (!isset($this->_with) && !isset($this->_without) && !isset($this->_if) && $valueIsNull) {
            $this->reason = 'Parameter is required';
            return $this->isValid = false;
        }
        if (isset($this->_with)) {
            /* all $with parameters must be present in the request 
             * as well as $parameter */
            $with = array_map(function ($parameter) {
                return is_null($parameter->getValue());
            }, $this->_with);
            if (in_array(true, $with, true)) {
                $this->reason = 'Missing required parameters';
                return $this->isValid = false;
            }
            if ($valueIsNull) {
                $this->reason = 'Parameter is required';
                return $this->isValid = false;
            }
        }
        if (isset($this->_without)) {
            // if none of $without parameters are present in the request 
            $without = array_map(function ($parameter) {
                return !is_null($parameter->getValue());
            }, $this->_without);
            /* if $parameter is missing and none of $without parameters 
             * are present... */
            if ($valueIsNull && empty(array_filter($without))) {
                $this->reason = 'Parameter is required';
                return $this->isValid = false;
            }
        }
        if (isset($this->_if)) {
            foreach ($this->_if as $v) {
                // $this->_if[] = ($v = [$otherParameter, $operator, $value]);
                if ($v instanceof Validate) {
                    if ($v->validate() && $valueIsNull) {
                        $this->reason = 'Parameter is required';
                        return $this->isValid = false;
                    }
                }
                if (is_bool($v[2])) {
                    $this->reason = 'Parameter is required';
                    return $this->isValid = $this->evalIfBool($v[0], $v[1], $v[2]) && !$valueIsNull;
                } elseif (is_float($v[2])) {
                    $this->reason = 'Parameter is required';
                    return $this->isValid = $this->evalIfFloat($v[0], $v[1], $v[2]) && !$valueIsNull;
                } elseif (is_int($v[2])) {
                    $this->reason = 'Parameter is required';
                    return $this->isValid = $this->evalIfInt($v[0], $v[1], $v[2]) && !$valueIsNull;
                } elseif (is_string($v[2])) {
                    $this->reason = 'Parameter is required';
                    return $this->isValid = $this->evalIfString($v[0], $v[1], $v[2]) && !$valueIsNull;
                }
            }
        }

        return $this->isValid = true;
    }

}
