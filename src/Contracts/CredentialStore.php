<?php

namespace Peppers\Contracts;

interface CredentialStore {

    /**
     * 
     * @param string $dotNotationName
     * @return array|null
     */
    public function get(string $dotNotationName): ?array;
}
