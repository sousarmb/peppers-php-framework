<?php

namespace Peppers\Base;

use JsonSerializable;

abstract class RequestParameter implements JsonSerializable {

    protected string $name;
    protected mixed $value;

    /**
     *
     * @return string
     */
    public function getName(): string {
        return $this->name;
    }

    /**
     * 
     * @return mixed
     */
    public function getValue(): mixed {
        return $this->value;
    }

    /**
     * 
     * @return mixed
     */
    public function __toString(): string {
        return $this->getValue() ?: '';
    }

    /**
     * 
     * @return object
     */
    public function jsonSerialize(): object {
        return (object) [$this->getName() => $this->getValue()];
    }

}
