<?php

namespace Peppers\Strategy\Boot;

use Peppers\Base\Strategy;
use Peppers\Services\CredentialStore as CredentialStoreService;
use RuntimeException;
use Settings;

class CredentialStore extends Strategy {

    public function __construct() {
        $this->allowedToFail = true;
    }

    /**
     * 
     * @return CredentialStoreService
     * @throws RuntimeException
     */
    public function default(): CredentialStoreService {
        $registryFile = 'credentials.php';
        if (!is_readable(Settings::get('APP_CONFIG_DIR') . $registryFile)) {
            $msg = sprintf(
                    'Could not read %s in %s',
                    $registryFile,
                    Settings::get('APP_CONFIG_DIR'));
            throw new RuntimeException($msg);
        }

        $credentials = include_once Settings::get('APP_CONFIG_DIR') . $registryFile;
        if (!Settings::appInProduction()) {
            $this->checkCredentials($credentials);
        }

        return new CredentialStoreService($credentials);
    }

    /**
     * 
     * @param array $credentials
     * @return void
     * @throws RuntimeException
     */
    private function checkCredentials(array &$credentials): void {
        if (!is_array($credentials)) {
            throw new RuntimeException('Credentials array not found');
        }
        if (!array_key_exists('default', $credentials)) {
            throw new RuntimeException('Missing default credentials');
        }
    }

}
