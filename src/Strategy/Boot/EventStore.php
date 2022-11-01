<?php

namespace Peppers\Strategy\Boot;

use RuntimeException;
use Peppers\Base\Strategy;
use Peppers\Contracts\EventHandler;
use Peppers\Services\EventStore as EventStoreService;
use Settings;

class EventStore extends Strategy {

    /**
     * 
     * @return EventStoreService
     * @throws RuntimeException
     */
    public function default(): EventStoreService {
        $registryFile = 'eventshandlers.php';
        if (!is_readable(Settings::get('APP_CONFIG_DIR') . $registryFile)) {
            $msg = sprintf(
                    'Could not read %s in %s',
                    $registryFile,
                    Settings::get('APP_CONFIG_DIR'));
            throw new RuntimeException($msg);
        }

        $eventsHandlers = include_once Settings::get('APP_CONFIG_DIR') . $registryFile;
        if (!Settings::appInProduction()) {
            $this->checkEventsHandlers($eventsHandlers);
        }

        return new EventStoreService($eventsHandlers);
    }

    /**
     * 
     * @param array $eventsHandlers
     * @return void
     * @throws RuntimeException
     */
    private function checkEventsHandlers(array &$eventsHandlers): void {
        foreach ($eventsHandlers as $event => $handlers) {
            // check for bad event handlers
            $badEventHandlers = array_filter(
                    $handlers,
                    fn($handler) => !is_string($handler) && !($handler instanceof EventHandler)
            );
            if ($badEventHandlers) {
                // bad handler found
                $msg = sprintf('Event %s has bad handler. Must be instance of %s or string class name for one that implements it',
                        $event,
                        EventHandler::class
                );
                throw new RuntimeException($msg);
            }
        }
    }

}
