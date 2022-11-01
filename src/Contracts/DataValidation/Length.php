<?php

namespace Peppers\Contracts\DataValidation;

interface Length {

    /**
     * 
     * @param int $value
     * @return self
     */
    public function setExactLength(int $value): self;

    /**
     * 
     * @param int $value
     * @return self
     */
    public function setMaxLength(int $value): self;

    /**
     * 
     * @param int $value
     * @return self
     */
    public function setMinLength(int $value): self;
}
