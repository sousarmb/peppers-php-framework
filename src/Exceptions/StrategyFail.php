<?php

namespace Peppers\Exceptions;

use Exception;

class StrategyFail extends Exception {

    public function __construct(string $strategyName) {
        parent::__construct(
                sprintf(
                        'Strategy %s failed or is not a valid implementation',
                        $strategyName
                )
        );
    }

}
