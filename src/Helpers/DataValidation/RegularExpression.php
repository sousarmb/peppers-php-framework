<?php

namespace Peppers\Helpers\DataValidation;

use Peppers\Base\Validator;

class RegularExpression extends Validator {

    private string $_pattern;

    /**
     * 
     * @param string|null $pattern  Regular expression
     * @param mixed $value
     */
    public function __construct(
            ?string $pattern = null,
            mixed $value = null,
    ) {
        $this->_pattern = $pattern;
        $this->value = $value;
    }

    /**
     * This method uses preg_match() internally
     * 
     * @return bool FALSE if preg_match() == [0|FALSE], TRUE otherwise
     */
    public function validate(): bool {
        $this->hasRun = true;
        $matches = preg_match($this->_pattern, $this->value);
        if (!$matches) {
            $this->reason = 'Does not pass regular expression';
            return $this->isValid = false;
        }

        return $this->isValid = true;
    }

}
