<?php

namespace Peppers\Contracts\DataValidation;

interface Range {

    /**
     * 
     * @param int|float|string $value
     * @return self
     */
    public function setMin(int|float|string $value): self;

    /**
     * 
     * @param int|float|string $value
     * @return self
     */
    public function setMax(int|float|string $value): self;

    /**
     * 
     * @param int|float|string $startValue
     * @param int|float|string $endValue
     * @return self
     */
    public function between(
            int|float|string $startValue,
            int|float|string $endValue
    ): self;

    /**
     * 
     * @param int|float|string $startValue
     * @param int|float|string $endValue
     * @return self
     */
    public function notBetween(
            int|float|string $startValue,
            int|float|string $endValue
    ): self;
}
