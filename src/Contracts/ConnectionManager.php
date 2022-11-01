<?php

namespace Peppers\Contracts;

use PDO;

interface ConnectionManager {

    /**
     * 
     * @param string $dotNotationCredentials
     * @param string $dotNotationDataSource
     * @return PDO
     */
    public function connectTo(
            string $dotNotationCredentials,
            string $dotNotationDataSource = 'default',
    ): PDO;
}
