<?php

namespace Peppers\Services;

use RuntimeException;
use Peppers\AppEvent;
use Peppers\Contracts\Services\Shutdown;
use Peppers\Contracts\EventStore as EventStoreContract;
use Peppers\Factory;

class EventStore implements EventStoreContract, Shutdown {

    private array $_deferred = [];
    private array $_eventHandlers = [];

    /**
     * 
     * @param array $eventConsumers
     */
    public function __construct(array $eventConsumers = []) {
        $this->_eventHandlers = $eventConsumers;
    }

    /**
     * 
     * @param AppEvent $event
     * @return AppEvent|null    AppEvent if the event is deferred (you can use 
     *                          later in your code
     * @throws RuntimeException
     */
    public function dispatch(AppEvent $event): ?AppEvent {
        $eventClass = $event::class;
        if (!array_key_exists($eventClass, $this->_eventHandlers)) {
            throw new RuntimeException("$eventClass not registered for consumption");
        }
        if ($event->getIsDeferred()) {
            $this->_deferred[] = $event;
            return $event;
        } else {
            $this->handle($event);
        }

        return null;
    }

    /**
     * 
     * @param AppEvent $event
     * @return void
     */
    private function handle(AppEvent $event): void {
        $eventClass = $event::class;
        foreach ($this->_eventHandlers[$eventClass] as $consumer) {
            if (is_object($consumer)) {
                $consumer->handle($event);
            } else {
                Factory::getClassInstance($consumer)->handle($event);
            }
        }
    }

    /**
     * Processes all deferred Event instances
     * 
     * @return void
     */
    public function handleDeferred(): void {
        if ($this->_deferred) {
            foreach ($this->_deferred as $event) {
                $this->handle($event);
            }
        }
    }

    /**
     * Alias for handleDeferred()
     * 
     * @return void
     */
    public function shutdown(): void {
        $this->handleDeferred();
    }

}
