<?php

namespace Peppers\Strategy\Boot;

use Closure;
use Peppers\Base\Strategy;
use App\Events\BuildRoutesCacheFileEvent;
use Peppers\Factory;
use Peppers\Exceptions\InvalidRouteHandler;
use Peppers\Exceptions\InvalidRouteRegister;
use Peppers\Exceptions\RouteNotFound;
use Peppers\Helpers\Http\FormRouteRegister;
use Peppers\Helpers\Http\RestfulRouteRegister;
use Peppers\RouteRegister;
use Peppers\Services\RouteResolver as RouteResolverService;
use RuntimeException;
use Settings;

class RouteResolver extends Strategy {

    /**
     * 
     * @return RouteResolverService
     * @throws RuntimeException
     * @throws InvalidRouteRegister
     */
    public function default(): RouteResolverService {
        $routesFileName = 'routes.php';
        $routesFile = Settings::get('APP_CONFIG_DIR') . $routesFileName;
        if (!is_readable($routesFile)) {
            $msg = sprintf(
                    'Could not read routes file %s',
                    $routesFileName
            );
            throw new RuntimeException($msg);
        } elseif (!Settings::appInProduction()) {
            return $this->useRoutesFile($routesFile);
        }

        $routesCacheFile = Settings::get('TEMP_DIR') . Settings::get('ROUTES_CACHE_FILENAME');
        if (!is_readable($routesCacheFile)) {
            /* cache does not exist, maybe first request after deployment, 
             * create it */
            $this->createRoutesCache();
            // ... and use the routes file for now
            return $this->useRoutesFile($routesFile);
        }

        return $this->useCacheFile($routesCacheFile);
    }

    /**
     * 
     * @param string $routesFile
     * @return RouteResolverService
     * @throws RouteNotFound
     * @throws InvalidRouteRegister
     */
    private function useRoutesFile(string $routesFile): RouteResolverService {
        $routes = include_once $routesFile;
        if (!$routes) {
            // no routes registered! stop right now!
            throw new RouteNotFound();
        }

        foreach ($routes as $key => $route) {
            $option_A = $route instanceof RouteRegister;
            $option_B = $route instanceof FormRouteRegister;
            $option_C = $route instanceof RestfulRouteRegister;
            if (!$option_A && !$option_B && !$option_C) {
                throw new InvalidRouteRegister(
                                $key,
                                [RestfulRouteRegister::class, RouteRegister::class]
                );
            }

            $this->validateUrl($route, $key);
            $this->validateHandler($route, $key);
        }

        return new RouteResolverService($routes);
    }

    /**
     * 
     * @param RestfulRouteRegister|RouteRegister $route
     * @param int $key
     * @return void
     * @throws InvalidRouteHandler
     * @throws RuntimeException
     */
    private function validateHandler(
            $route,
            int $key
    ): void {
        $handler = $route->getHandler();
        $expected = is_array($handler) || is_string($handler) || $handler instanceof Closure;
        if (!$expected) {
            throw new InvalidRouteHandler($key, gettype($handler));
        }
        if (is_array($handler)) {
            list($class, $method) = $handler;
            $check_A = is_string($class) && !empty($class);
            $check_B = is_string($method) && !empty($method);
            if ($check_A && $check_B) {
                return;
            }

            throw new RuntimeException(
                            sprintf('Bad route handler class/method at %s position',
                                    $key
                            )
            );
        } elseif (is_string($handler) && empty($handler)) {
            throw new RuntimeException(
                            sprintf('Empty string for route handler at %s position',
                                    $key
                            )
            );
        }
    }

    /**
     * 
     * @param RestfulRouteRegister|RouteRegister $route
     * @param int $key
     * @return void
     * @throws RuntimeException
     */
    private function validateUrl(
            $route,
            int $key
    ): void {
        $path = sprintf('%s://%s%s',
                $route->getIsSecure() ? 'https' : 'http',
                $_SERVER['SERVER_NAME'],
                $route->getPath()
        );
        if (!filter_var($path, FILTER_VALIDATE_URL)) {
            throw new RuntimeException(
                            sprintf(
                                    'Invalid path in route at %s position',
                                    $key
                            )
            );
        }
    }

    /**
     * 
     * @param string $routesCacheFile
     * @return RouteResolverService
     */
    private function useCacheFile(string $routesCacheFile): RouteResolverService {
        $handle = fopen($routesCacheFile, 'r');
        $routeResolverService = stream_get_contents($handle);
        fclose($handle);
        return unserialize($routeResolverService);
    }

    /**
     * 
     * @return void
     */
    private function createRoutesCache(): void {
        Factory::getClassInstance(BuildRoutesCacheFileEvent::class)
                ->setIsDeferred(true)
                ->dispatch();
    }

}
