<?php

namespace Peppers\Base;

use RuntimeException;
use Peppers\Contracts\IO;

abstract class File implements IO {

    private bool $_closed = false;
    protected $handle;

    /**
     * 
     * @return bool
     */
    public function close(): bool {
        if (!$this->_closed) {
            $this->_closed = fclose($this->handle);
        }

        return $this->_closed;
    }

    /**
     * 
     * @param int $bytes
     * @return mixed
     */
    public function read(int $bytes): mixed {
        return fread($this->handle, $bytes);
    }

    /**
     * 
     * @param mixed $bytes
     * @return mixed
     */
    public function write(mixed $bytes): mixed {
        return fwrite($this->handle, $bytes);
    }

    /**
     * 
     * @throws RuntimeException
     */
    public function __clone() {
        throw new RuntimeException('Cannot clone file');
    }

    /**
     * close() the file
     */
    public function __destruct() {
        $this->close();
    }

}
