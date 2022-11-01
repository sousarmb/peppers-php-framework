<?php

namespace Peppers\Helpers;

abstract class Dates {

    /**
     * 
     * @param int|float|string $value
     * @return DateTime
     * @throws RuntimeException
     */
    public static function getDateFromScalar(int|float|string $value): DateTime {
        if (is_int($value)) {
            $dateObj = (new DateTime())->setTimestamp($value);
        } elseif (is_float($value)) {
            $dateObj = (new DateTime())->setTimestamp(intval($value, 10));
        } else {
            // string
            $dateObj = new DateTime($value);
        }
        if ($dateObj instanceof DateTime) {
            return $dateObj;
        }

        throw new RuntimeException('Invalid date/time');
    }

}
