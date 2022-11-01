<?php

namespace Peppers\Helpers\Http;

use Closure;
use Peppers\RouteRegister;
use Peppers\Helpers\Http\FormRouteRegister;
use Peppers\Helpers\Http\RestfulRouteRegister;

final class Route {

    /**
     *
     * @param string $path
     * @return RouteRegister
     */
    public static function delete(
            string $path,
            array|Closure|string $handler
    ): RouteRegister {
        return new RouteRegister('DELETE', $path, $handler);
    }

    /**
     *
     * @param string $path
     * @return RouteRegister
     */
    public static function get(
            string $path,
            array|Closure|string $handler
    ): RouteRegister {
        return new RouteRegister('GET', $path, $handler);
    }

    /**
     *
     * @param string $path
     * @return RouteRegister
     */
    public static function head(
            string $path,
            array|Closure|string $handler
    ): RouteRegister {
        return new RouteRegister('HEAD', $path, $handler);
    }

    /**
     *
     * @param string $path
     * @return RouteRegister
     */
//    public static function patch(
//        string $path,
//        array|Closure $handler
//    ): RouteRegister {
//        return new RouteRegister('PATCH', $path, $handler);
//    }

    /**
     *
     * @param string $path
     * @return RouteRegister
     */
    public static function post(
            string $path,
            array|Closure|string $handler
    ): RouteRegister {
        return new RouteRegister('POST', $path, $handler);
    }

    /**
     *
     * @param string $path
     * @return RouteRegister
     */
//    public static function put(
//        string $path,
//        array|Closure $handler
//    ): RouteRegister {
//        return new RouteRegister('PUT', $path, $handler);
//    }

    /**
     * This is a helper method: it is the equivalent to the developer writing 
     * the routes in the routes file. This route later expands into the routes 
     * for the DELETE, GET, HEAD, POST methods.
     * Upon expansion, model primary key columns are added as path parameters.
     * 
     * @param string $path
     * @param string $modelRepositoryClass
     * @return RestfulRouteRegister
     */
    public static function restful(
            string $path,
            string $modelRepositoryClass
    ): RestfulRouteRegister {
        return new RestfulRouteRegister($path, $modelRepositoryClass);
    }
    
    /**
     * This is a helper method: it is the equivalent to the developer writing 
     * the routes in the routes file. This route later expands into the routes 
     * for the GET and POST methods.
     * 
     * @param string $path
     * @param string $handler
     * @return FormRouteRegister
     */
    public static function form(
            string $path,
            string $handler
    ): FormRouteRegister {
        return new FormRouteRegister($path, $handler);
    }

}
