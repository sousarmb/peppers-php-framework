<?php

namespace Peppers\Services;

use Peppers\Contracts\CredentialStore as CredentialStoreContract;
use Peppers\Helpers\Arrays;

class CredentialStore implements CredentialStoreContract {

    private array $_credentials;

    /**
     * 
     * @param array $credentials
     */
    public function __construct(array $credentials) {
        $this->_credentials = $credentials;
    }

    /**
     * 
     * @param string $dotNotationName
     * @return array|null
     */
    public function get(string $dotNotationName): ?array {
        if ($dotNotationName == 'default') {
            $dotNotationName = $this->_credentials['default'];
        }

        return Arrays::getFrom(
                        $this->_credentials,
                        $dotNotationName
        );
    }

}
