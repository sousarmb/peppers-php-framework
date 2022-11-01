<?php

namespace Peppers\Contracts;

interface IO {

    /**
     * 
     * @return bool
     */
    public function close(): bool;

    /**
     * 
     * @param string $filePath
     * @return bool
     */
    public static function exists(string $filePath): bool;

    /**
     * 
     * @param string $location
     * @param string $mode
     * @return mixed
     */
    public static function open(
            string $location,
            string $mode = 'r'
    ): mixed;

    /**
     * 
     * @param int $bytes
     * @return mixed
     */
    public function read(int $bytes): mixed;

    /**
     * 
     * @param mixed $bytes
     * @return mixed
     */
    public function write(mixed $bytes): mixed;
}
