<?php

namespace Peppers\Helpers\Sql;

use Peppers\Contracts\ModelRepository;
use Peppers\Contracts\Promise;
use Peppers\Helpers\Sql\Conditions;

class DeletePromise implements Promise {

    private int $_limit;
    private array $_orderBy;
    private ModelRepository $_repository;
    private bool $_resolved = false;
    private string $_sql;
    private array $_sqlValues;
    private Conditions $_where;

    /**
     * 
     * @param ModelRepository $repository
     */
    public function __construct(ModelRepository $repository) {
        $this->_repository = $repository;
    }

    /**
     * 
     * @param Conditions $where
     * @return self
     */
    public function where(Conditions $where): self {
        $this->_where = $where;
        return $this;
    }

    /**
     * 
     * @param int $rowCount
     * @return self
     */
    public function limit(int $rowCount = 1024): self {
        $this->_limit = $rowCount;
        return $this;
    }

    /**
     * 
     * @param string $column
     * @param string $direction
     * @return self
     */
    public function orderBy(
            string $column,
            string $direction = 'ASC'
    ): self {
        $this->_orderBy[] = "$column $direction";
        return $this;
    }

    /**
     * 
     * @return Conditions|null
     */
    public function getWhere(): ?Conditions {
        return isset($this->_where) ? $this->_where : null;
    }

    /**
     * 
     * @return array
     */
    public function getOrderBy(): array {
        return isset($this->_orderBy) ? $this->_orderBy : null;
    }

    /**
     * 
     * @return int
     */
    public function resolve(): int {
        $this->_sqlValues = [];
        $this->_sql = sprintf('UPDATE %s SET deleted_on=NOW() ',
                $this->_repository->getTableName()
        );
        if (isset($this->_where)) {
            $this->_sql .= ' WHERE ' . $this->_where->resolve();
            $this->_sqlValues = array_merge(
                    $this->_sqlValues,
                    $this->_where->get(true)
            );
            $this->_sql .= ' AND deleted_on IS NULL';
        }
        if (isset($this->_limit)) {
            $this->_sql .= ' LIMIT ?';
            array_push(
                    $this->_sqlValues,
                    $this->_limit
            );
        }
        if (isset($this->_orderBy)) {
            $this->_sql .= ' ORDER BY ?';
            array_push(
                    $this->_sqlValues,
                    implode(',', $this->_orderBy)
            );
        }

        $this->_resolved = true;
        return $this->_repository->resolveDeletePromise($this);
    }

    /**
     * 
     * @return array
     */
    public function getSqlValues(): array {
        return $this->_sqlValues;
    }

    /**
     * 
     * @return string
     */
    public function getSql(): string {
        return $this->_sql;
    }

    /**
     * 
     * @return bool
     */
    public function isResolved(): bool {
        return $this->_resolved;
    }

}
