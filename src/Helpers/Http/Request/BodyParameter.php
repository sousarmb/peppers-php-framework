<?php

namespace Peppers\Helpers\Http\Request;

use Peppers\Base\RequestParameter;
use Peppers\Contracts\RouteResolver;
use Peppers\ServiceLocator;
use Peppers\Services\RequestBody;
use RuntimeException;

class BodyParameter extends RequestParameter {

    /**
     *
     * @param string $name
     * @param mixed $defaultValue
     * @throws Exception
     */
    public function __construct(
            string $name,
            mixed $defaultValue = null
    ) {
        $httpMethod = ServiceLocator::get(RouteResolver::class)
                ->getResolved()
                ->getHttpMethod();
        if ($httpMethod != 'POST') {
            throw new RuntimeException('Cannot access request body');
        }

        $requestBody = ServiceLocator::get(RequestBody::class);
        $value = strpos($name, '.') === false ? $requestBody->$name : $requestBody->find($name);
        $this->value = is_null($value) ? $defaultValue : $value;
        $this->name = $name;
    }

}
