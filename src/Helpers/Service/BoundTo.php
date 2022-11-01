<?php

namespace Peppers\Helpers\Service;

class BoundTo {

    private string $_implementation;

    /**
     *
     * @param string $implementation
     */
    public function __construct(
            string $implementation
    ) {
        $this->_implementation = $implementation;
    }

    /**
     *
     * @return string
     */
    public function getName(): string {
        return $this->_implementation;
    }

}
