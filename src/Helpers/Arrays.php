<?php

namespace Peppers\Helpers;

use Closure;
use RecursiveArrayIterator;
use RecursiveIteratorIterator;

abstract class Arrays {

    private const DEFAULT_SEPARATOR = '.';

    /**
     * Flatten a N-dimensional array
     * 
     * @param array $array
     * @param bool $preserveKeys
     * @return array
     */
    public static function flatten(
            array $array,
            bool $preserveKeys = false
    ): array {
        $recursiveArrayIterator = new RecursiveArrayIterator(
                $array,
                RecursiveArrayIterator::CHILD_ARRAYS_ONLY
        );
        $iterator = new RecursiveIteratorIterator($recursiveArrayIterator);
        return iterator_to_array(
                $iterator,
                $preserveKeys
        );
    }

    /**
     * 
     * @param array $haystack
     * @param array|string $needle
     * @return mixed
     */
    public static function getFrom(
            array $haystack,
            array|string $needle,
    ): mixed {
        if (is_string($needle)) {
            $needle = explode(static::DEFAULT_SEPARATOR, $needle);
        }
        if (is_numeric($needle)) {
            $needle = (int) $needle;
        }
        if ($needle) {
            $current = array_shift($needle);
        } else {
            return $haystack;
        }
        if (array_key_exists($current, $haystack) && is_array($haystack[$current])) {
            return static::getFrom($haystack[$current], $needle);
        } elseif (count($needle) > 0) {
            return null;
        }

        return $haystack[$current];
    }

    /**
     * Sort (in place) an array of array by key value and direction
     * 
     * @param array $arrayToSort
     * @param array $keyDirection [['key', 'asc|desc'], ...]
     * @return void
     */
    public static function sortArrayByKeysAndDirection(
            array &$arrayToSort,
            array $keyDirection
    ): void {
        uasort($arrayToSort,
                static::arraySortingFunction($keyDirection)
        );
    }

    /**
     * 
     * @param array $parameters
     * @return Closure
     */
    private static function arraySortingFunction(array $parameters): Closure {
        return function (array $a, array $b) use ($parameters) {
            return array_reduce(
            $parameters,
            function ($result, $key) use ($a, $b) {
                if ($result) {
                    return $result;
                }
                list($column, $order) = $key;
                if (is_int($a[$column]) || is_float($a[$column])) {
                    return $order == 'asc' || $order == 'ASC' ? $a[$column] <=> $b[$column] : $b[$column] <=> $a[$column];
                }
                // string
                return $order == 'asc' || $order == 'ASC' ? call_user_func('strcmp', $a[$column], $b[$column]) : call_user_func('strcmp', $b[$column], $a[$column]);
            }
            );
        };
    }

    /**
     * Sort (in place) an array of objects of the same class type, by member 
     * value and direction
     * 
     * @param array $arrayToSort
     * @param array $memberDirection [['member', 'asc|desc'], ...]
     * @return void
     */
    public static function sortObjectCollectionByMemberAndDirection(
            array &$arrayToSort,
            array $memberDirection
    ): void {
        uasort($arrayToSort,
                static::objectSortingFunction($memberDirection)
        );
    }

    /**
     * 
     * @param array $parameters
     * @return Closure
     */
    private static function objectSortingFunction(array $parameters): Closure {
        return function (object $a, object $b) use ($parameters) {
            return array_reduce(
            $parameters,
            function ($result, $key) use ($a, $b) {
                if ($result) {
                    return $result;
                }
                list($column, $order) = $key;
                if (is_int($a->$column) || is_float($a->$column)) {
                    return $order == 'asc' || $order == 'ASC' ? $a->$column <=> $b->$column : $b->$column <=> $a->$column;
                }
                // string
                return $order == 'asc' || $order == 'ASC' ? call_user_func('strcmp', $a->$column, $b->$column) : call_user_func('strcmp', $b->$column, $a->$column);
            }
            );
        };
    }

}
