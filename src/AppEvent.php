<?php

namespace Peppers;

use Peppers\Contracts\EventStore;

abstract class AppEvent {

    protected bool $deferred = true;
    protected array $data = [];
    private EventStore $_eventStore;

    /**
     * 
     * @param EventStore $eventStore
     */
    public function __construct(EventStore $eventStore) {
        $this->_eventStore = $eventStore;
    }

    /**
     * 
     * @return self|null
     */
    public function dispatch(): ?self {
        return $this->_eventStore->dispatch($this);
    }

    /**
     * 
     * @return array
     */
    public function getData(): array {
        return $this->data;
    }

    /**
     * 
     * @param array $data
     * @return self
     */
    public function setData(array $data = []): self {
        $this->data = $data;
        return $this;
    }

    /**
     * 
     * @return bool
     */
    public function getIsDeferred(): bool {
        return $this->deferred;
    }

    /**
     * 
     * @param bool $noYes
     * @return self
     */
    public function setIsDeferred(bool $noYes = true): self {
        $this->deferred = $noYes;
        return $this;
    }

}
