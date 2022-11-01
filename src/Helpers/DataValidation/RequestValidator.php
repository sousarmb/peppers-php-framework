<?php

namespace Peppers\Helpers\DataValidation;

use RuntimeException;
use Peppers\Contracts\DataValidation\RequestValidator as RequestValidatorContract;
use Peppers\Contracts\DataValidation\Validate;
use Peppers\Exceptions\InterruptValidation;
use Peppers\Base\RequestParameter;
use Peppers\Helpers\DataValidation\ValidationGroup;

class RequestValidator implements RequestValidatorContract {

    private bool $_hasRun = false;
    private bool $_isValid = false;
    private array $_validators = [];

    /**
     * The validator instance that interrupted the flow
     * @var Validate
     */
    private Validate $interrupt;

    /**
     * 
     * @param string $name
     * @return ValidationGroup
     * @throws RuntimeException
     */
    public function getValidationFor(string $name): ValidationGroup {
        if (array_key_exists($name, $this->_validators)) {
            return $this->_validators[$name][1];
        }

        throw new RuntimeException("No validation group registered for parameter $name");
    }

    /**
     * 
     * @return bool
     */
    public function hasRun(): bool {
        return $this->_hasRun;
    }

    /**
     * 
     * @param RequestParameter $parameter
     * @param bool $interrupts
     * @return ValidationGroup
     */
    public function check(
            RequestParameter $parameter,
            bool $interrupts = false
    ): ValidationGroup {
        $this->_validators[$parameter->getName()] = [
            $parameter,
            new ValidationGroup($this, $interrupts)
        ];
        return $this->_validators[$parameter->getName()][1];
    }

    /**
     * 
     * @param bool $instancesOrMessages FALSE get the failed validator(s) 
     *                                  collection; TRUE just error messages
     * @return array of Peppers\Contracts\DataValidation\Validate 
     *                                  or field_name => validator_error_messages
     */
    public function failed(bool $instancesOrMessages = false): array {
        if (!$this->_hasRun) {
            $this->validate();
        }

        $collection = array_filter(
                array_map(
                        fn($validationGroup) => $validationGroup[1]->failed(),
                        $this->_validators
                )
        );
        if (!$collection) {
            // no errors
            return $collection;
        } elseif ($instancesOrMessages) {
            // return field_name => validator_error_messages
            return array_map(
                    fn($validators) => array_map(
                            fn($validator) => $validator->getReason(),
                            $validators
                    ),
                    $collection
            );
        }

        return $collection;
    }

    /**
     * 
     * @return array of Peppers\Contracts\DataValidation\Validate
     */
    public function passed(): array {
        if (!$this->_hasRun) {
            $this->validate();
        }

        return array_filter(
                array_map(
                        fn($validationGroup) => $validationGroup[1]->passed(),
                        $this->_validators
                )
        );
    }

    /**
     * 
     * @return bool
     */
    public function validate(): bool {
        if ($this->_hasRun) {
            return $this->_isValid;
        }
        $this->_hasRun = true;
        try {
            $isValid = array_map(
                    function ($validationGroup) {
                        list($parameter, $group) = $validationGroup;
                        return $group->run($parameter->getValue());
                    },
                    $this->_validators
            );
        } catch (InterruptValidation $iv) {
            $this->interrupt = $iv->getFault();
            return $this->_isValid = false;
        }
        return $this->_isValid = isset($isValid) ? !in_array(false, $isValid, true) : false;
    }

    /**
     * 
     * @return bool
     */
    public function wasInterrupted(): bool {
        return isset($this->interrupt);
    }

    /**
     * 
     * @return Validate
     */
    public function getInterrupt(): Validate {
        return $this->interrupt;
    }

    /**
     * Throw away previous validation groups, reset the instance
     * 
     * @return self
     */
    public function reset(): self {
        $this->_hasRun = false;
        $this->_isValid = false;
        $this->_validators = [];
        return $this;
    }

}
