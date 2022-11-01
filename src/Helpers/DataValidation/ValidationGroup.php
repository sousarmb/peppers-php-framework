<?php

namespace Peppers\Helpers\DataValidation;

use Peppers\Contracts\DataValidation\RequestValidator;
use Peppers\Contracts\DataValidation\Validate;
use Peppers\Contracts\PipelineStage;
use Peppers\Exceptions\InterruptValidation;
use Peppers\Helpers\Http\Request\BodyParameter;
use Peppers\Helpers\Http\Request\FileParameter;
use Peppers\Helpers\Http\Request\PathParameter;
use Peppers\Helpers\Http\Request\QueryParameter;

class ValidationGroup implements PipelineStage {

    private RequestValidator $_controller;
    private array $_failed = [];
    private bool $_interrupts;
    private bool $_isValid;
    private array $_passed = [];
    private array $_validators = [];

    /**
     * 
     * @param RequestValidator $requestValidator
     * @param bool $interrupts
     */
    public function __construct(
            RequestValidator $requestValidator,
            bool $interrupts
    ) {
        $this->_controller = $requestValidator;
        $this->_interrupts = $interrupts;
    }

    /**
     * 
     * @param Validate $validator
     * @return self
     */
    public function with(Validate $validator): self {
        $this->_validators[] = $validator;
        return $this;
    }

    /**
     * 
     * @param mixed $io
     * @return mixed
     */
    public function run(mixed $io): mixed {
        $that = $this;
        $results = array_map(function ($validator) use ($io, $that) {
            $isValid = $validator->setValue($io)->validate();
            $isValid ? $that->_passed[] = $validator : $that->_failed[] = $validator;
            if (!$isValid && $that->_interrupts) {
                $that->_isValid = false;
                // interrupt 
                throw new InterruptValidation($validator);
            }
            return $isValid;
        }, $this->_validators);
        return $this->_isValid = in_array(true, $results, true);
    }

    /**
     * 
     * @return array
     */
    public function failed(): array {
        return $this->_failed;
    }

    /**
     * 
     * @return array
     */
    public function passed(): array {
        return $this->_passed;
    }

    /**
     * 
     * @param BodyParameter|FileParameter|PathParameter|QueryParameter $parameter
     * @param bool $interrupts
     * @return ValidationGroup
     */
    public function check(
            BodyParameter|FileParameter|PathParameter|QueryParameter $parameter,
            bool $interrupts = false
    ): ValidationGroup {
        return $this->_controller->check($parameter, $interrupts);
    }

}
