<?php

namespace Peppers\Helpers\Service;

use Closure;
use Peppers\Helpers\Service\Descriptor;
use Peppers\Contracts\Services\AbstractImplementation as iAbstractImplementation;

class AbstractImplementation extends Descriptor implements iAbstractImplementation {

    private array $_bindings = [];
    private Closure|string $_provider;

    /**
     *
     * @param string $implementation
     * @param array $bindings
     */
    public function __construct(
            string $implementation,
            array $bindings = []
    ) {
        $this->implementation = $implementation;
        $this->_bindings = $bindings;
    }

    /**
     *
     * @return array
     */
    public function getBindings(): array {
        return $this->_bindings;
    }

    /**
     *
     * @return Closure|string
     */
    public function getProvider(): Closure|string {
        return $this->_provider;
    }

    /**
     *
     * @return bool
     */
    public function hasBindings(): bool {
        return !empty($this->_bindings);
    }

    /**
     *
     * @param Closure|string $provider  If Closure, any class used within must 
     *                                  be called using its fully qualified 
     *                                  domain name. If string, the fully 
     *                                  qualified domain name for the provider 
     *                                  class.
     * @return self
     */
    public function setProvider(
            Closure|string $provider
    ): self {
        $this->_provider = $provider;
        return $this;
    }

}
