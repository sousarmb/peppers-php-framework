<?php

namespace Peppers\Helpers;

use RuntimeException;

class ViewDataStore {

    protected array $data = [];
    protected bool $readOnly = false;

    /**
     * 
     * @param array $data
     */
    public function __construct(array $data = []) {
        foreach ($data as $key => $value) {
            $this->data[$key] = $value;
        }
    }

    /**
     * 
     * @param string $name
     * @return type
     * @throws RuntimeException
     */
    public function __get(string $name): mixed {
        if ($this->has($name)) {
            return is_string($this->data[$name]) ? htmlentities($this->data[$name]) : $this->data[$name];
        }

        return htmlentities("<!--not-found-$name>");
    }

    /**
     * 
     * @param string $name
     * @param mixed $value
     * @return void
     * @throws RuntimeException
     */
    public function __set(
            string $name,
            mixed $value
    ): void {
        if ($this->readOnly) {
            throw new RuntimeException('View data is read-only');
        }

        $this->data[$name] = $value;
    }

    /**
     * 
     * @param string $name
     * @return bool
     */
    public function has(string $name): bool {
        return array_key_exists($name, $this->data);
    }

    /**
     * Make view data read-only!
     * 
     * @return self
     */
    public function protect(): self {
        $this->readOnly = true;
        return $this;
    }

}
