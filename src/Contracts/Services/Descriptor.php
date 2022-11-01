<?php

namespace Peppers\Contracts\Services;

interface Descriptor {

    /**
     * 
     * @return string
     */
    public function getImplementation(): string;

    /**
     * 
     * @return bool
     */
    public function getIsSingleton(): bool;

    /**
     * 
     * @return bool
     */
    public function getIsSingletonLoaded(): bool;

    /**
     * 
     * @param bool $noYes
     * @return self
     */
    public function setIsSingleton(bool $noYes): self;

    /**
     * 
     * @return void
     */
    public function setSingletonLoaded(): void;
}
