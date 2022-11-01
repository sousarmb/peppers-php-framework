<?php

namespace Peppers\Contracts;

use Peppers\Contracts\Resolver;
use Peppers\RouteRegister;

interface RouteResolver extends Resolver {

    /**
     * 
     * @return RouteRegister|null
     */
    public function getResolved(): ?RouteRegister;
}
