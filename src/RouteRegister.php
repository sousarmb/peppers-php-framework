<?php

namespace Peppers;

use Closure;
use Peppers\Helpers\Types\ReturnType;
use Peppers\Helpers\Closures;
use Settings;

class RouteRegister {

    private bool $_allowFreeQuery;
    private array|Closure|string $_handler;
    private string $_httpMethod;
    private bool $_isSecure = false;
    private string $_path;
    private array $_pathExpressions = [];
    private array $_queryExpressions = [];

    /**
     *
     * @param string $httpMethod
     * @param string $path
     * @param array|Closure|string $handler
     */
    public function __construct(
            string $httpMethod,
            string $path,
            array|Closure|string $handler
    ) {
        $this->_allowFreeQuery = Settings::get('HTTP_ALLOW_FREE_QUERY');
        $this->_httpMethod = $httpMethod;
        $this->_path = strpos($path, '?') === false ? $path : str_replace('?', '\?', $path);
        $this->_handler = $handler;
    }

    /**
     *
     * @return bool
     */
    public function getAllowFreeQuery(): bool {
        return $this->_allowFreeQuery;
    }

    /**
     * 
     * @return array|Closure|string
     */
    public function getHandler(): array|Closure|string {
        return $this->_handler;
    }

    /**
     *
     * @return bool
     */
    public function getHasPathRegex(): bool {
        return !empty($this->_pathExpressions);
    }

    /**
     *
     * @return bool
     */
    public function getHasQueryRegex(): bool {
        return !empty($this->_queryExpressions);
    }

    /**
     *
     * @return string
     */
    public function getHttpMethod(): string {
        return $this->_httpMethod;
    }

    /**
     *
     * @return bool
     */
    public function getIsHandlerClosure(): bool {
        return $this->_handler instanceof Closure;
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
    public function getPath(
            ReturnType $type = ReturnType::string
    ): array|string {
        if ($type == ReturnType::array) {
            $pathArray = explode('/', $this->_path);
            array_shift($pathArray);
            return $pathArray;
        }

        return $this->_path;
    }

    /**
     *
     * @param string $parameter
     * @return array
     */
    public function getPathExpressions(
            string $parameter = ''
    ): array {
        return $parameter ? $this->_pathExpressions[$parameter] : $this->_pathExpressions;
    }

    /**
     *
     * @param string $parameter
     * @return array
     */
    public function getQueryExpressions(
            null|string $parameter = null
    ): array {
        return is_null($parameter) ? $this->_queryExpressions : $this->_queryExpressions[$parameter];
    }

    /**
     *
     * @return string
     */
    private function preparePathRegex(): string {
        $pathExpressions = $this->getPathExpressions();
        $keys = array_keys($pathExpressions);
        $expressions = array_values($pathExpressions);
        array_walk(
                $keys,
                function (&$value) {
                    $value = '{' . $value . '}';
                }
        );
        array_walk(
                $expressions,
                function (&$value) {
                    $value = "$value";
                }
        );
        return str_replace(
                $keys,
                $expressions,
                $this->getPath()
        );
    }

    /**
     *
     * @return string
     */
    private function prepareQueryRegex(): string {
        // if the developer hardcoded some query in the route path...
        $queryExpression = strpos($this->getPath(), '?') === false ? '\?' : '&';
        if ($this->getHasQueryRegex()) {
            $queryParameters = [];
            foreach ($this->getQueryExpressions() as $key => $expression) {
                $queryParameters[] = "$key=$expression";
            }
            $queryExpression .= implode('&', $queryParameters);
        }

        return $queryExpression;
    }

    /**
     *
     * @param bool $noYes   Allow "uncontrolled" query in URL. Default value 
     *                      set in Settings::get('HTTP_ALLOW_FREE_QUERY').
     * @return self
     */
    public function setAllowFreeQuery(
            bool $noYes
    ): self {
        $this->_allowFreeQuery = $noYes;
        return $this;
    }

    /**
     *
     * @param bool $noYes
     * @return self
     */
    public function setIsSecure(
            bool $noYes
    ): self {
        $this->_isSecure = $noYes;
        return $this;
    }

    /**
     *
     * @param string $parameter
     * @param null|string $expression
     * @return self
     */
    public function setPathExpression(
            string $parameter,
            null|string $expression
    ): self {
        $this->_pathExpressions[$parameter] = $expression ?: Settings::get('REGEX_DEFAULT_ROUTE_PARAMETER');
        return $this;
    }

    /**
     *
     * @param string $parameter
     * @param null|string $expression
     * @return self
     */
    public function setQueryExpression(
            string $parameter,
            null|string $expression
    ): self {
        $this->_queryExpressions[$parameter] = $expression ?: Settings::get('REGEX_DEFAULT_ROUTE_PARAMETER');
        return $this;
    }

    /**
     *
     * @return string
     */
    public function toRegex(): string {
        $routeExpression = $this->getHasPathRegex() ? $this->preparePathRegex() : $this->getPath();
        if ($this->getHasQueryRegex()) {
            $routeExpression .= $this->prepareQueryRegex();
        }
        if ($this->getAllowFreeQuery()) {
            // the developer allows more query
            $routeExpression .= Settings::get('REGEX_DEFAULT_ROUTE_FREE_QUERY');
        }

        return "~^$routeExpression$~i";
    }

    /**
     * Return an array representation of the object. If the route  handler is 
     * an instance of Closure, it will be serialized into a string using 
     * ClosureDump::dumpRoute()
     * 
     * @return array
     */
    public function __serialize(): array {
        return [
            $this->_allowFreeQuery,
            $this->_handler instanceof Closure ? Closures::serialize($this->_handler) : $this->_handler,
            $this->_httpMethod,
            $this->_isSecure,
            $this->_path,
            $this->_pathExpressions,
            $this->_queryExpressions
        ];
    }

    /**
     * Prepares the register for use. If the handler is a string starting with 
     * '$this->' that means it's a Closure instance that must eval()'d and its 
     * instance set to the register handler member.
     * 
     * @param array $serializedRegister
     * @return void
     */
    public function __unserialize(array $serializedRegister): void {
        $this->_allowFreeQuery = $serializedRegister[0];
        if (is_array($serializedRegister[1])) {
            $this->_handler = $serializedRegister[1];
        } elseif (strpos($serializedRegister[1], 'function') === 0) {
            $this->_handler = Closures::unserialize($serializedRegister[1]);
        } else {
            $this->_handler = $serializedRegister[1];
        }

        $this->_httpMethod = $serializedRegister[2];
        $this->_isSecure = $serializedRegister[3];
        $this->_path = $serializedRegister[4];
        $this->_pathExpressions = $serializedRegister[5];
        $this->_queryExpressions = $serializedRegister[6];
    }

}
