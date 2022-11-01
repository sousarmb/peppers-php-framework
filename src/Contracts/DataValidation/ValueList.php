<?php

namespace Peppers\Contracts\DataValidation;

interface ValueList {

    /**
     * 
     * @return bool
     */
    public function isAllowed(): bool;

    /**
     * 
     * @return bool
     */
    public function isForbidden(): bool;

    /**
     * 
     * @param array $values
     * @return self
     */
    public function setAllowed(array $values): self;

    /**
     * 
     * @param array $values
     * @return self
     */
    public function setForbidden(array $values): self;
}
