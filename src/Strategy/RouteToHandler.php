<?php

namespace Peppers\Strategy;

use Closure;
use Peppers\Base\ModelRepository;
use Peppers\Contracts\FormHandler;
use Peppers\Contracts\PipelineStage;
use Peppers\Factory;
use Peppers\Helpers\ResponseSent;
use Peppers\Response;
use Peppers\RestfulModel;
use Peppers\RouteRegister;
use Peppers\View;
use RuntimeException;

class RouteToHandler implements PipelineStage {

    /**
     *
     * @param mixed $io
     * @return mixed
     * @throws RuntimeException
     */
    public function run(
            mixed $io
    ): mixed {
        $handler = $io->getHandler();
        switch ($handler) {
            case is_array($handler):
                return $this->useControllerMethod($handler);

            case ($handler instanceof Closure):
                return $this->useClosure($handler);

            default:
                /* string; should be an implementation of ModelRepository 
                 * or View class or FormHandler contract */
                $implementation = Factory::getClassInstance($handler);
                if ($implementation instanceof ModelRepository) {
                    return $this->useModelRepository($implementation, $io);
                } elseif ($implementation instanceof FormHandler) {
                    return $this->useFormHandler($implementation, $io);
                } else {
                    return $this->useView($implementation);
                }
        }
    }

    /**
     *
     * @param Closure $closure
     * @return mixed
     */
    private function useClosure(Closure $closure): mixed {
        return Factory::getClassInstance($closure);
    }

    /**
     *
     * @param array $handler
     * @return mixed
     */
    private function useControllerMethod(array $handler): mixed {
        list($implementation, $methodName) = $handler;
        list($methodInstance, $classInstance, $methodParameters) = Factory::getMethodInstance($methodName, $implementation);
        return $methodInstance->invokeArgs(
                        $classInstance,
                        $methodParameters
        );
    }

    /**
     *
     * @param ModelRepository $repository
     * @param RouteRegister $routeRegister
     * @return Response
     */
    private function useModelRepository(
            ModelRepository $repository,
            RouteRegister $routeRegister
    ): Response {
        return (new RestfulModel($repository, $routeRegister))->default();
    }

    /**
     * 
     * @param FormHandler $implementation
     * @param RouteRegister $routeRegister
     * @return Response|ResponseSent
     */
    private function useFormHandler(
            FormHandler $implementation,
            RouteRegister $routeRegister
    ): Response|ResponseSent {
        $methodToCall = strtolower($routeRegister->getHttpMethod());
        return $implementation->$methodToCall();
    }

    /**
     *
     * @param View $implementation
     * @return string
     */
    private function useView(View $implementation): string {
        // not implemented...
        http_response_code(501);
        exit;
    }

}
