<?php

namespace Peppers\Services;

use App\Controllers\Peppers\DefaultController;
use Generator;
use Peppers\Contracts\RouteResolver as RouteResolverContract;
use Peppers\Helpers\Http\FormRouteRegister;
use Peppers\Helpers\Http\RestfulRouteRegister;
use Peppers\Helpers\Types\ReturnType;
use Peppers\RouteRegister;
use Settings;

class RouteResolver implements RouteResolverContract {

    private array $_expanded;
    private array $_index;
    private int $_matchPosition;
    private array $_pathValues = [];
    private array $_queryValues = [];
    private array $_routeRegistry = [];

    /**
     * 
     * @param array $routeRegistry
     */
    public function __construct(array &$routeRegistry) {
        $current = reset($routeRegistry);
        $key = 0;
        do {
            // $key = key($routeRegistry);
            if ($current instanceof RestfulRouteRegister || $current instanceof FormRouteRegister) {
                foreach ($current->expand() as $route) {
                    $this->_routeRegistry[] = $route;
                    $this->_expanded[$route->getHttpMethod()][] = $route->toRegex();
                    $this->_index[$route->getHttpMethod()][] = $key++;
                }
            } else {
                $this->_routeRegistry[] = $current;
                $this->_expanded[$current->getHttpMethod()][] = $current->toRegex();
                $this->_index[$current->getHttpMethod()][] = $key++;
            }
        } while ($current = next($routeRegistry));
    }

    /**
     * 
     * @param string $name
     * @return mixed
     */
    public function getResolvedQueryValue(string $name): mixed {
        return array_key_exists($name, $this->_queryValues) ? $this->_queryValues[$name] : null;
    }

    /**
     * 
     * @param string $name
     * @return string|null
     */
    public function getResolvedPathValue(string $name): ?string {
        return array_key_exists($name, $this->_pathValues) ? $this->_pathValues[$name] : null;
    }

    /**
     *
     * @return Generator
     */
    private function getRoutesToResolve(): Generator {
        $stop = false;
        /**
         * @todo Implement a search/sorting algorithm to prevent linear search
         */
        $httpMethod = $_SERVER['REQUEST_METHOD'];
        foreach ($this->_expanded[$httpMethod] as $k => $regexRoute) {
            /* yield the position in the route registry and the route string
             * to be preg_match (ed) */
            $stop = (yield $k => $regexRoute);
            // calling code sent a stop sign?
            if ($stop) {
                break;
            }
        }
    }

    /**
     *
     * @return RouteRegister|null
     */
    public function resolve(): ?RouteRegister {
        if (isset($this->_matchPosition)) {
            return $this->_routeRegistry[$this->_matchPosition];
        } elseif (empty($this->_routeRegistry)) {
            // no routes available to resolve...
            return $this->getDefaultController();
        }
        // encoded URL? decode just in case
        $requestURI = urldecode($_SERVER['REQUEST_URI']);
        foreach ($generator = $this->getRoutesToResolve() as $matchPosition => $regexRoute) {
            $matches = preg_match($regexRoute, $requestURI);
            if ($matches) {
                // stop iterating
                $generator->send(true);
                break;
            }
        }
        // if no route's request method matches the actual request then ...
        if (!isset($matches) || !$matches) {
            // no match, use default controller?
            return $this->getDefaultController();
        }
        /* using super global again because when developer sets a query/path
         * regular expression with options|without|capturing|groups sometimes
         * we don't get the correct path value in this phase (or i don't know 
         * what i'm doing wrong) */
        $matches = parse_url($requestURI);
        // and now store the values the client to be used in the app
        $matchPosition = $this->_index[$_SERVER['REQUEST_METHOD']][$matchPosition];
        $register = $this->_routeRegistry[$matchPosition];
        if ($register->getHasPathRegex()) {
            $this->setPathExpressionValues(
                    $matches['path'],
                    $matchPosition
            );
        }
        if ($register->getHasQueryRegex()) {
            $this->setQueryExpressionValues(
                    $matches['query'],
                    $matchPosition
            );
        } elseif ($register->getAllowFreeQuery() && array_key_exists('query', $matches)) {
            // check for "free" query
            $this->setFreeQueryValues($matches['query']);
        }

        $this->_matchPosition = $matchPosition;
        return $this->_routeRegistry[$this->_matchPosition];
    }

    /**
     * Alias for self::resolve()
     * 
     * @return RouteRegister|null
     */
    public function getResolved(): ?RouteRegister {
        return $this->resolve();
    }

    /**
     * Set path/query key=>value that the client sent in the request
     * and store them in the resolver so the developer can access it later
     * without resorting to super global variables
     *
     * @param string $requestPath
     * @param int $registryPosition
     * @return void
     */
    private function setPathExpressionValues(
            string $requestPath,
            int $registryPosition
    ): void {
        /* the resource the client is requesting
         * (match from the route registry) */
        $register = $this->_routeRegistry[$registryPosition];
        // set the values for the path part of the resource URL
        $pathParts = explode('/', $requestPath);
        array_shift($pathParts);
        foreach ($register->getPath(ReturnType::array) as $position => $parameter) {
            if ($parameter[0] != '{') {
                continue;
            }
            // remove {} placeholder markers
            $tmpParameterName = substr(substr($parameter, 0, -1), 1);
            // last path part? if so get rid of the query part
            $this->_pathValues[$tmpParameterName] = $pathParts[$position];
        }
    }

    /**
     * Set path/query key=>value that the client sent in the request
     * and store them in the resolver so the developer can access it later
     * without resorting to super global variables
     *
     * @param string $requestQuery
     * @param int $registryPosition
     * @return void
     */
    private function setQueryExpressionValues(
            string $requestQuery,
            int $registryPosition
    ): void {
        // the route register the client is requesting
        $register = $this->_routeRegistry[$registryPosition];
        // parse the query in the URL to store for later use
        parse_str($requestQuery, $queryParts);
        $queryExpressions = $register->getQueryExpressions();
        if ($queryParts === $queryExpressions) {
            /* this happens when the developer didn't set a regular 
             * expression for the query value */
            return;
        } else {
            // with this we'll also store unregistered query values
            array_walk_recursive(
                    $queryParts,
                    function (&$v) {
                        $v = trim($v);
                    });
            $this->_queryValues = $queryParts;
        }
    }

    /**
     * 
     * @param string $requestQuery
     * @return void
     */
    private function setFreeQueryValues(
            string $requestQuery
    ): void {
        $queryParts = [];
        parse_str($requestQuery, $queryParts);
        $missing = array_diff_assoc($queryParts, $this->_queryValues);
        array_walk_recursive(
                $missing,
                function (&$v) {
                    $v = trim($v);
                });
        $this->_queryValues = array_merge($this->_queryValues, $missing);
    }

    /**
     * 
     * @return RouteRegister|null
     */
    private function getDefaultController(): ?RouteRegister {
        if (!Settings::get('USE_DEFAULT_CONTROLLER_ON_404')) {
            return null;
        }
        $uniqueID = session_status() == PHP_SESSION_ACTIVE ? session_id() : uniqid();
        $register = new RouteRegister(
                $_SERVER['REQUEST_METHOD'],
                "/404-not-found/$uniqueID",
                [DefaultController::class, 'default']
        );
        return $register->setIsSecure($_SERVER['REQUEST_SCHEME'] == 'https')
                        ->setAllowFreeQuery(false);
    }

    /**
     * 
     * @return array
     */
    public function __sleep(): array {
        return [
            '_expanded',
            '_index',
            '_routeRegistry'
        ];
    }

}
