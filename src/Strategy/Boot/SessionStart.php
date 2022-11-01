<?php

namespace Peppers\Strategy\Boot;

use Peppers\Base\Strategy;

class SessionStart extends Strategy {

    public function __construct() {
        $this->allowedToFail = false;
    }

    /**
     * 
     * @return bool
     */
    public function default(): bool {
        return session_start();
    }

}
