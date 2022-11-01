<?php

namespace Peppers\Helpers\Http;

use Peppers\RouteRegister;
use RuntimeException;

class Redirect {

    private RouteRegister|string $_location;
    private bool $_registerOrString;
    private bool $_temporary;

    /**
     * 
     * @param RouteRegister|string $location
     * @param bool $temporary
     * @throws RuntimeException
     */
    public function __construct(
            RouteRegister|string $location,
            bool $temporary = true
    ) {
        if (is_string($location)) {
            if (strpos($location, 'http') !== 0) {
                $location = sprintf('%s://%s%s',
                        $_SERVER['REQUEST_SCHEME'],
                        $_SERVER['HTTP_HOST'],
                        $location
                );
            }
            if (!filter_var($location, FILTER_VALIDATE_URL)) {
                throw new RuntimeException('Invalid URL to redirect to');
            }

            $this->_registerOrString = true;
        } else {
            $this->_registerOrString = false;
        }

        $this->_location = $location;
        $this->_temporary = $temporary;
    }

    /**
     * 
     * @return RouteRegister|string
     */
    public function getLocation(): RouteRegister|string {
        return $this->_location;
    }

    /**
     * 
     * @param RouteRegister|string $location
     * @return self
     */
    public function setLocation(RouteRegister|string $location): self {
        $this->_location = $location;
        return $this;
    }

    /**
     * 
     * @return bool
     */
    public function isTemporary(): bool {
        return $this->_temporary;
    }

    /**
     * 
     * @param bool $temporary
     * @return self
     */
    public function setTemporary(bool $temporary = true): self {
        $this->_temporary = $temporary;
        return $this;
    }

    /**
     * 
     * @return string
     */
    public function resolve(): string {
        if ($this->_registerOrString) {
            return $this->_location;
        }

        $path = $this->_location->getPath();
        if ($this->_location->getHasPathRegex()) {
            $pathExpressions = $this->_location->getPathExpressions();
            array_walk($pathExpressions, function (&$v) {
                $v = urlencode($v);
            });
            foreach ($pathExpressions as $k => $v) {
                $path = str_replace('{' . $k . '}', $v, $path);
            }
        }
        if ($this->_location->getHasQueryRegex()) {
            $path .= '?' . http_build_query($this->_location->getQueryExpressions());
        }

        return ($this->_location->getIsSecure() ? 'https' : 'http') . '://' . $path;
    }

}
