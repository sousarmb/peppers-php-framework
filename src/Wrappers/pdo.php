<?php

namespace Peppers\Wrappers;

use PDO as NativePDO;
use PDOStatement;
use Settings;

class pdo extends NativePDO {

    private array $_queries = [];
    private bool $_storeQueries;

    /**
     * 
     * @param string $dsn
     * @param string $username
     * @param string $password
     */
    public function __construct(
            string $dsn,
            string $username,
            string $password
    ) {
        parent::__construct(
                $dsn,
                $username,
                $password
        );
        parent::setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        parent::setAttribute(PDO::ATTR_CASE, PDO::CASE_NATURAL);
        $this->_storeQueries = !Settings::appInProduction();
    }

    /**
     * 
     * @return array
     */
    public function getQueries(): array {
        return $this->_queries;
    }

    /**
     * 
     * @param string $statement
     * @return int|false
     */
    public function exec(string $statement): int|false {
        if ($this->_storeQueries) {
            $this->_queries[] = $statement;
        }

        return parent::exec($statement);
    }

    /**
     * 
     * @param string $query
     * @param array $options
     * @return PDOStatement|false
     */
    public function prepare(
            string $query,
            array $options = []
    ): PDOStatement|false {
        if ($this->_storeQueries) {
            $this->_queries[] = $query . '|' . implode('|', $options);
        }

        return parent::prepare($query, $options);
    }

    /**
     * 
     * @param string $query
     * @param int|null $fetchMode
     * @param mixed $fetchModeArgs
     * @return PDOStatement|false
     */
    public function query(
            string $query,
            ?int $fetchMode = null,
            mixed ...$fetchModeArgs
    ): PDOStatement|false {
        if ($this->_storeQueries) {
            $this->_queries[] = $query;
        }

        return parent::query($query, $fetchMode, $fetchModeArgs);
    }

}
