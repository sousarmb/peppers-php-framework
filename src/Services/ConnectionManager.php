<?php

namespace Peppers\Services;

use Peppers\Contracts\ConnectionManager as ConnectionManagerContract;
use Peppers\Helpers\Arrays;
use Peppers\ServiceLocator;
use Peppers\Contracts\CredentialStore;
use PDO;
use RuntimeException;

class ConnectionManager implements ConnectionManagerContract {

    protected CredentialStore $credentials;
    private array $_connections;
    protected array $dataSources;
    protected array $wrappers;

    /**
     * 
     * @param array $dataSources
     * @param CredentialStore|null $credentials
     */
    public function __construct(
            array $dataSources,
            ?CredentialStore $credentials = null
    ) {
        $this->dataSources = $dataSources;
        // 1st level is the wrapper name
        $wrappers = array_keys($dataSources);
        // drop the default
        array_shift($wrappers);
        $this->wrappers = $wrappers;
        $this->credentials = $credentials ?: ServiceLocator::get(CredentialStore::class);
    }

    /**
     * 
     * @param string $dotNotationDataSource
     * @param array $source
     * @param array $credentials
     * @return PDO
     * @throws RuntimeException
     */
    private function useWrapper(
            string $dotNotationDataSource,
            array $source,
            array $credentials
    ): PDO {
        $wrapperName = substr($dotNotationDataSource, 0, strpos($dotNotationDataSource, '.'));
        if (!in_array($wrapperName, $this->wrappers)) {
            throw new RuntimeException("Unavailable datasource wrapper $wrapperName");
        }

        $wrapperName = 'Peppers\\Wrappers\\' . $wrapperName;
        return new $wrapperName(
                $source['dsn'],
                $credentials['user'],
                $credentials['password']
        );
    }

    /**
     * 
     * @param string $dotNotationCredentials
     * @param string|null $dotNotationDataSource
     * @return PDO
     * @throws RuntimeException
     */
    public function connectTo(
            string $dotNotationCredentials,
            string $dotNotationDataSource = 'default',
    ): PDO {
        if (isset($this->_connections) && array_key_exists($dotNotationDataSource, $this->_connections)) {
            return $this->_connections[$dotNotationDataSource];
        }

        $credentials = $this->credentials->get($dotNotationCredentials);
        if (!$credentials) {
            throw new RuntimeException('Credentials not found');
        }
        if ($dotNotationDataSource == 'default') {
            $dotNotationDataSource = $this->dataSources['default'];
        }

        $source = Arrays::getFrom(
                        $this->dataSources,
                        $dotNotationDataSource
        );
        if (is_null($source)) {
            throw new RuntimeException("Unknown datasource $dotNotationDataSource");
        }

        $conn = $this->useWrapper(
                $dotNotationDataSource,
                $source,
                $credentials
        );
        return $this->_connections[$dotNotationDataSource] = $conn;
    }

    /**
     * 
     * @param string|null $dotNotationDataSource
     * @return PDO|null
     */
    public function getConn(?string $dotNotationDataSource = null): ?PDO {
        if (isset($this->_connections) && array_key_exists($dotNotationDataSource, $this->_connections)) {
            return $this->_connections[$dotNotationDataSource];
        }

        return null;
    }

}
