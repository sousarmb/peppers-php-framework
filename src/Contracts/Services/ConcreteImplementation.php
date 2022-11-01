<?php

namespace Peppers\Contracts\Services;

use Peppers\Contracts\Services\Descriptor;

interface ConcreteImplementation extends Descriptor {

    /**
     * 
     * @return bool
     */
    public function getIsLazyLoad(): bool;

    /**
     * 
     * @param bool $noYes
     * @return self
     */
    public function setIsLazyLoad(bool $noYes): self;
}
