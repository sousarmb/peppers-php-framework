<?php

namespace Peppers\Helpers\Http;

use Generator;
use Peppers\Helpers\Types\ReturnType;
use Peppers\RouteRegister;

class FormRouteRegister {

    private string $_handler;
    private bool $_isSecure = false;
    private string $_path;

    /**
     *
     * @param string $path
     * @param string $handler
     */
    public function __construct(
            string $path,
            string $handler
    ) {
        $this->_path = $path;
        $this->_handler = $handler;
    }

    /**
     *
     * @return string
     */
    public function getHandler(): string {
        return $this->_handler;
    }

    /**
     *
     * @return bool
     */
    public function getIsSecure(): bool {
        return $this->_isSecure;
    }

    /**
     *
     * @param ReturnType $type
     * @return array|string
     */
    public function getPath(ReturnType $type = ReturnType::string): array|string {
        if ($type == ReturnType::array) {
            $pathArray = explode('/', $this->_path);
            array_shift($pathArray);
            return $pathArray;
        }

        return $this->_path;
    }

    /**
     *
     * @param bool $noYes
     * @return self
     */
    public function setIsSecure(bool $noYes): self {
        $this->_isSecure = $noYes;
        return $this;
    }

    /**
     *
     * @return Generator
     */
    public function expand(): Generator {
        /**
         * GET route:
         * - get the form
         * - no query allowed
         */
        yield $this->createRoute(
                        'GET',
                        false,
                        $this->_isSecure
        );
        /**
         * GET route:
         * - get the form
         * - query allowed
         */
        yield $this->createRoute(
                        'GET',
                        true,
                        $this->_isSecure
        );
        /**
         * POST route:
         * - process form
         * - no query allowed
         */
        yield $this->createRoute(
                        'POST',
                        false,
                        $this->_isSecure
        );
    }

    /**
     * 
     * @param string $method
     * @param bool $allowFreeQuery
     * @param bool $isSecure
     * @return RouteRegister
     */
    private function createRoute(
            string $method,
            bool $allowFreeQuery,
            bool $isSecure
    ): RouteRegister {
        $route = new RouteRegister(
                $method,
                $this->_path,
                $this->_handler
        );
        return $route->setAllowFreeQuery($allowFreeQuery)
                        ->setIsSecure($isSecure);
    }

}
