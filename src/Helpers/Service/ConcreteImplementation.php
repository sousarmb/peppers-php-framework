<?php

namespace Peppers\Helpers\Service;

use Peppers\Contracts\Services\ConcreteImplementation as iConcreteImplementation;
use Peppers\Helpers\Service\Descriptor;

class ConcreteImplementation extends Descriptor implements iConcreteImplementation {

    private bool $_isLazyLoad = true;

    /**
     *
     * @param string $implementation
     */
    public function __construct(
            string $implementation
    ) {
        $this->implementation = $implementation;
    }

    /**
     *
     * @return bool
     */
    public function getIsLazyLoad(): bool {
        return $this->_isLazyLoad;
    }

    /**
     *
     * @param bool $noYes
     * @return self
     */
    public function setIsLazyLoad(bool $noYes): self {
        $this->_isLazyLoad = $noYes;
        return $this;
    }

}
