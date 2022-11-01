<?php

namespace Peppers\Strategy\Boot;

use Peppers\Base\Strategy;
use Peppers\Services\ConnectionManager as ConnectionManagerService;
use RuntimeException;
use Settings;

class ConnectionManager extends Strategy {

    public function __construct() {
        $this->allowedToFail = true;
    }

    /**
     * 
     * @return ConnectionManagerService
     * @throws RuntimeException
     */
    public function default(): ConnectionManagerService {
        $registryFile = 'datasources.php';
        if (!is_readable(Settings::get('APP_CONFIG_DIR') . $registryFile)) {
            $msg = sprintf(
                    'Could not read %s in %s',
                    $registryFile,
                    Settings::get('APP_CONFIG_DIR'));
            throw new RuntimeException($msg);
        }

        $dataSources = include_once Settings::get('APP_CONFIG_DIR') . $registryFile;
        if (!Settings::appInProduction()) {
            $this->checkDataSources($dataSources);
        }

        return new ConnectionManagerService($dataSources);
    }

    /**
     * 
     * @param array $dataSources
     * @return void
     * @throws RuntimeException
     */
    private function checkDataSources(array &$dataSources): void {
        if (!is_array($dataSources)) {
            throw new RuntimeException('Datasources array not found');
        }
        if (!array_key_exists('default', $dataSources)) {
            throw new RuntimeException('Missing default datasource');
        }
    }

}
