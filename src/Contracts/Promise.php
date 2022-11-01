<?php

namespace Peppers\Contracts;

interface Promise {

    /**
     * 
     * @return bool
     */
    public function isResolved(): bool;

    /**
     * 
     * @return mixed
     */
    public function resolve(): mixed;
}
