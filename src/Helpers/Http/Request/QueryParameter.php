<?php

namespace Peppers\Helpers\Http\Request;

use Peppers\Base\RequestParameter;
use Peppers\ServiceLocator;
use Peppers\Contracts\RouteResolver;

class QueryParameter extends RequestParameter {

    /**
     *
     * @param string $name
     */
    public function __construct(
            string $name,
            mixed $defaultValue = null
    ) {
        $value = ServiceLocator::get(RouteResolver::class)
                ->getResolvedQueryValue($name);
        $this->value = is_null($value) ? $defaultValue : $value;
        $this->name = $name;
    }

}
