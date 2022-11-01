<?php

namespace Peppers\Helpers\Service;

use Peppers\Contracts\Services\Descriptor as iDescriptor;
use RuntimeException;

abstract class Descriptor implements iDescriptor {

    protected string $implementation;
    protected bool $isSingleton = false;
    protected bool $isSingletonLoaded = false;

    /**
     *
     * @return string
     */
    public function getImplementation(): string {
        return $this->implementation;
    }

    /**
     *
     * @return bool
     */
    public function getIsSingleton(): bool {
        return $this->isSingleton;
    }

    /**
     *
     * @return bool
     */
    public function getIsSingletonLoaded(): bool {
        return $this->isSingletonLoaded;
    }

    /**
     *
     * @param bool $noYes
     * @return self
     */
    public function setIsSingleton(
            bool $noYes
    ): self {
        $this->isSingleton = $noYes;

        return $this;
    }

    /**
     *
     * @return void
     * @throws Exception
     */
    public function setSingletonLoaded(): void {
        if (!$this->isSingleton) {
            throw new RuntimeException('Trying to set singleton flag in non-singleton ' . get_called_class());
        }

        $this->isSingletonLoaded = true;
    }

}
