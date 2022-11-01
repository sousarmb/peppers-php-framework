<?php

namespace Peppers\Base;

use Peppers\Contracts\DefaultMethod;

abstract class Strategy implements DefaultMethod {

    protected bool $allowedToFail = false;

    /**
     * 
     * @return bool
     */
    public function allowedToFail(): bool {
        return $this->allowedToFail;
    }

}
