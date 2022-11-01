<?php

namespace Peppers\Contracts;

interface Seekable {

    /**
     * 
     * @return bool
     */
    public function isSeekable(): bool;

    /**
     * 
     * @return bool
     */
    public function rewind(): bool;

    /**
     * 
     * @param int $offset
     * @return int
     */
    public function seek(int $offset = 0): int;

    /**
     * 
     * @return bool|int
     */
    public function tell(): bool|int;
}
