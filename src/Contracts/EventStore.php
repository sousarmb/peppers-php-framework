<?php

namespace Peppers\Contracts;

use Peppers\AppEvent;

interface EventStore {

    /**
     * 
     * @param AppEvent $event
     * @return AppEvent|null
     */
    public function dispatch(AppEvent $event): ?AppEvent;
}
