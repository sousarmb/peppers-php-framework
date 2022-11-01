<?php

namespace Peppers\Helpers;

use RuntimeException;

abstract class Strings {

    private static string $_uniqueId;

    /**
     * 
     * @return string
     */
    private static function seedUniqueId(): string {
        return self::$_uniqueId = uniqid();
    }

    /**
     * 
     * @param int $stringSize
     * @return string
     * @throws RuntimeException
     */
    public static function getUniqueId(int $stringSize = 13): string {
        if (empty(self::$_uniqueId)) {
            self::seedUniqueId();
        }
        if ($stringSize == 0) {
            throw new RuntimeException('Cannot return empty string');
        }
        if ($stringSize < 0) {
            $stringSize = abs($stringSize);
        }

        $tempUniqueId = self::$_uniqueId;
        while ($stringSize > strlen($tempUniqueId)) {
            $tempUniqueId .= self::$_uniqueId;
        }
        return substr(str_shuffle($tempUniqueId), 0, $stringSize);
    }

}
