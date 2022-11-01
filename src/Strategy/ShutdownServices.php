<?php

namespace Peppers\Strategy;

use Peppers\Base\Strategy;
use Peppers\Contracts\Services\Shutdown;
use Peppers\Exceptions\StrategyFail;
use Peppers\Kernel;
use Peppers\ServiceLocator;
use Throwable;

class ShutdownServices extends Strategy {

    public function __construct() {
        $this->allowedToFail = true;
    }

    /**
     * 
     * @return mixed
     * @throws StrategyFail
     */
    public function default(): mixed {
        foreach (ServiceLocator::getAllServices() as $service) {
            try {
                if ($service instanceof Shutdown) {
                    $service->shutdown();
                }
            } catch (Throwable $t) {
                /* if the EventStore service was already destroyed there is
                 * nowhere to keep the fact that a service shutdown failed 
                 * except for the panic log, which goes directly to file */
                Kernel::panic($t);
            }
        }
        return true;
    }

}
