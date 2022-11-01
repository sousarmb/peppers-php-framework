<?php

namespace Peppers\Contracts\Services;

use Closure;
use Peppers\Contracts\Services\Descriptor;

interface AbstractImplementation extends Descriptor {

    /**
     * 
     * @return array
     */
    public function getBindings(): array;

    /**
     * 
     * @return Closure|string
     */
    public function getProvider(): Closure|string;

    /**
     * 
     * @return bool
     */
    public function hasBindings(): bool;

    /**
     * 
     * @param Closure|string $provider
     * @return self
     */
    public function setProvider(Closure|string $provider): self;
}
