<?php

namespace Peppers\Strategy\Boot;

use Peppers\Base\Strategy;
use Peppers\Contracts\Services\Descriptor;
use Peppers\Contracts\Services\AbstractImplementation;
use Peppers\Contracts\Services\ConcreteImplementation;
use Peppers\ServiceLocator as SL;
use RuntimeException;
use Settings;

class ServiceLocator extends Strategy {

    /**
     * 
     * @return mixed
     * @throws RuntimeException
     */
    public function default(): mixed {
        $registryFile = 'services.php';
        if (!is_readable(Settings::get('APP_CONFIG_DIR') . $registryFile)) {
            $msg = sprintf(
                    'Could not read %s in %s',
                    $registryFile,
                    Settings::get('APP_CONFIG_DIR'));
            throw new RuntimeException($msg);
        }

        $serviceDescriptors = include_once Settings::get('APP_CONFIG_DIR') . $registryFile;
        if (!Settings::appInProduction()) {
            $this->checkServiceDescriptors($serviceDescriptors);
        }
        SL::boot($serviceDescriptors);
        return true;
    }

    /**
     *
     * @return array
     * @throws RuntimeException
     */
    private function checkServiceDescriptors(
            array &$serviceDescriptors
    ): array {
        foreach ($serviceDescriptors as $key => $descriptor) {
            if (!($descriptor instanceof Descriptor)) {
                $msg = sprintf(
                        'Invalid service descriptor at %s position, must be instance of %s or %s',
                        $key,
                        AbstractImplementation::class,
                        ConcreteImplementation::class
                );
                throw new RuntimeException($msg);
            }
            if (($descriptor instanceof AbstractImplementation) && $descriptor->hasBindings()) {
                $this->checkDescriptorBindings($descriptor);
            }
        }
        return $serviceDescriptors;
    }

    /**
     *
     * @param AbstractImplementation $descriptor
     * @return void
     * @throws RuntimeException
     */
    private function checkDescriptorBindings(
            AbstractImplementation $descriptor
    ): void {
        foreach ($descriptor->getBindings() as $bindingKey => $binding) {
            if (!is_string($binding) || !strlen($binding)) {
                $msg = sprintf(
                        '%s has invalid binding in position %s',
                        $descriptor->getImplementation(),
                        $bindingKey
                );
                throw new RuntimeException($msg);
            }
        }
    }

}
