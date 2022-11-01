<?php

namespace Peppers\Helpers;

use Closure;
use ReflectionFunction;

abstract class Closures {

    /**
     * 
     * @param string $closureString
     * @return Closure
     */
    public static function unserialize(string $closureString): Closure {
        eval('$closure = ' . $closureString);
        return $closure;
    }

    /**
     * 
     * @param Closure $closure
     * @param bool $removeNewLine
     * @return string
     */
    public static function serialize(
            Closure $closure,
            bool $removeNewLine = true
    ): string {
        $reflection = new ReflectionFunction($closure);
        $closureParameters = array_map(
                function ($parameter) {
                    $parameterDeclaration = '';
                    if ($parameter->getType()) {
                        $parameterDeclaration .= $parameter->getType()->getName() . ' ';
                    }
                    if ($parameter->isPassedByReference()) {
                        $parameterDeclaration .= '&';
                    }
                    $parameterDeclaration .= '$' . $parameter->name;
                    if ($parameter->isOptional()) {
                        $parameterDeclaration .= ' = ' . var_export($parameter->getDefaultValue(), true);
                    }

                    return $parameterDeclaration;
                },
                $reflection->getParameters()
        );
        $closureString = sprintf('function (%s) {' . PHP_EOL,
                implode(', ', $closureParameters)
        );
        $lines = file($reflection->getFileName());
        for ($current = $reflection->getStartLine(), $end = $reflection->getEndLine(); $current < $end; $current++) {
            $closureString .= $lines[$current];
        }
        /* if the last character in the line is not the closing '}' then all 
         * remaining characters must be removed */
        $closureString = substr($closureString, 0, strrpos($closureString, '}') + 1) . ';';
        if ($removeNewLine) {
            $closureString = str_replace(PHP_EOL, '', $closureString);
        }

        return $closureString;
    }

}
