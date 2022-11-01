<?php

namespace Peppers\Contracts;

use Peppers\AppEvent;

interface EventHandler {

    /**
     * 
     * @param AppEvent $event
     * @return bool
     */
    public function handle(AppEvent $event): bool;
}
