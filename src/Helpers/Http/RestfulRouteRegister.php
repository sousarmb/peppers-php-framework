<?php

namespace Peppers\Helpers\Http;

use Generator;
use Peppers\Factory;
use Peppers\Helpers\Types\ReturnType;
use Peppers\RouteRegister;
use Settings;

class RestfulRouteRegister {

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
        if (strpos($path, '?') !== false) {
            throw new UnexpectedValueException('No query registration allowed in ' . __CLASS__);
        }

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
        $repository = Factory::getClassInstance($this->_handler);
        $model = new ($repository->getModelClass())();
        $pathParameters = implode(
                '/',
                array_map(
                        fn($key) => '{' . $key . '}',
                        $model->getPrimaryKeyColumns()
                )
        );
        $primaryKeyColumns = $model->getPrimaryKeyColumns();
        /*
         * DELETE route:
         * - delete a specific model instance
         * - primary key in URL path
         */
        yield $this->createRoute(
                        'DELETE',
                        false,
                        $this->_isSecure,
                        $pathParameters,
                        $primaryKeyColumns
        );
        /**
         * GET route:
         * - get a specific model instance
         * - primary key in URL path
         */
        yield $this->createRoute(
                        'GET',
                        false,
                        $this->_isSecure,
                        $pathParameters,
                        $primaryKeyColumns
        );
        /**
         * GET route:
         * - get a collection of records
         * - no primary key in URL path
         * - allows for search by model column/property 
         */
        yield $this->createRoute(
                        'GET',
                        true,
                        $this->_isSecure,
                        null,
                        null
        );
        /**
         * HEAD route:
         * - get headers for a specific model instance
         * - primary key in URL path
         */
        yield $this->createRoute(
                        'HEAD',
                        false,
                        $this->_isSecure,
                        $pathParameters,
                        $primaryKeyColumns
        );
        /**
         * HEAD route:
         * - get headers for a collection of records
         * - no primary key in URL path
         * - allows for search by model column/property 
         */
        yield $this->createRoute(
                        'HEAD',
                        true,
                        $this->_isSecure,
                        null,
                        null
        );
        /**
         * POST route:
         * - update model instance
         * - primary key in URL path
         * - no free query
         */
        yield $this->createRoute(
                        'POST',
                        false,
                        $this->_isSecure,
                        $pathParameters,
                        $primaryKeyColumns
        );
        /**
         * POST route:
         * - create model instance
         * - no primary key in URL path
         * - no free query
         */
        yield $this->createRoute(
                        'POST',
                        false,
                        $this->_isSecure,
                        null,
                        null
        );
    }

    /**
     * 
     * @param string $method
     * @param bool $allowFreeQuery
     * @param bool $isSecure
     * @param string|null $pathParameters
     * @param array|null $primaryKeyColumns
     * @return RouteRegister
     */
    private function createRoute(
            string $method,
            bool $allowFreeQuery,
            bool $isSecure,
            ?string $pathParameters,
            ?array $primaryKeyColumns
    ): RouteRegister {
        $route = new RouteRegister(
                $method,
                $this->_path . ($pathParameters ? '/' . $pathParameters : ''),
                $this->_handler
        );
        if ($pathParameters) {
            foreach ($primaryKeyColumns as $key) {
                $route->setPathExpression(
                        $key,
                        Settings::get('MODEL_PRIMARY_KEY_DEFAULT_REGEX')
                );
            }
        }

        return $route->setAllowFreeQuery($allowFreeQuery)
                        ->setIsSecure($isSecure);
    }

}
