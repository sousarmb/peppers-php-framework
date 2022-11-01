<?php

namespace Peppers\Helpers\Sql;

use Generator;
use Peppers\Contracts\Model;
use Peppers\Contracts\ModelRepository;
use Peppers\Contracts\Promise;
use Peppers\Helpers\Sql\Conditions;
use RuntimeException;

class DataPromise implements Promise {

    private array $_groupBy;
    private Conditions $_having;
    private int $_limit;
    private Model $_model;
    private int $_offset;
    private array $_orderBy;
    private ModelRepository $_repository;
    private bool $_resolved = false;
    private array $_select = [];
    private string $_sql;
    private array $_sqlValues = [];
    private Conditions $_where;
    private bool $_withDeleted = false;
    private bool $_withTimeStamp = false;

    /**
     * 
     * @param ModelRepository $repository
     * @param Model $model
     */
    public function __construct(
            ModelRepository $repository,
            Model $model
    ) {
        $this->_repository = $repository;
        $this->_model = $model;
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
     * @param string $column
     * @return self
     */
    public function groupBy(string $column): self {
        if (!$this->_model->hasColumn($column)) {
            throw new RuntimeException("Unknown model column $column");
        }

        $this->_groupBy[] = $column;
        return $this;
    }

    /**
     * 
     * @return bool
     */
    public function hasGrouping(): bool {
        return isset($this->_groupBy);
    }

    /**
     * 
     * @param Conditions $having
     * @return self
     */
    public function having(Conditions $having): self {
        $this->_having = $having;
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
     * @param int $offset
     * @return self
     */
    public function offset(int $offset): self {
        $this->_offset = $offset;
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
        if (!$this->_model->hasColumn($column)) {
            throw new RuntimeException("Unknown model column $column");
        } elseif ($direction != 'ASC') {
            $direction = 'DESC';
        }

        $this->_orderBy[] = "$column $direction";
        return $this;
    }

    /**
     * 
     * @param array $columns
     * @return self
     */
    public function select(array $columns): self {
        $this->_select = array_merge($this->_select, $columns);
        return $this;
    }

    /**
     * Add timestamp columns - created_on, updated_on, deleted_on - to result
     * set
     * 
     * @return void
     */
    public function withTimeStamp(): void {
        $this->_withTimeStamp = true;
    }

    /**
     * SELECT deleted records as well
     * 
     * @return self
     */
    public function withDeleted(): self {
        $this->_withDeleted = true;
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
    public function getSelect(): array {
        return $this->_select;
    }

    /**
     * 
     * @return array|null
     */
    public function getOrderBy(): ?array {
        return isset($this->_orderBy) ? $this->_orderBy : null;
    }

    /**
     * 
     * @return int|null
     */
    public function getOffset(): ?int {
        return isset($this->_offset) ? $this->_offset : null;
    }

    /**
     * 
     * @return int|null
     */
    public function getLimit(): ?int {
        return $this->_limit;
    }

    /**
     * 
     * @return Conditions|null
     */
    public function getHaving(): ?Conditions {
        return isset($this->_having) ? $this->_having : null;
    }

    /**
     * 
     * @return array|null
     */
    public function getGroupBy(): ?array {
        return isset($this->_groupBy) ? $this->_groupBy : null;
    }

    /**
     * 
     * @return Generator
     */
    public function resolve(): Generator {
        foreach ($this->_repository->resolveDataPromise($this) as $pk => $model) {
            yield $pk => $model;
        }
        $this->_resolved = true;
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
        if (isset($this->_sql)) {
            return $this->_sql;
        }

        $this->_sqlValues = [];
        if (!isset($this->_groupBy)) {
            $primaryKey = $this->_model->getPrimaryKeyColumns();
            if (count($primaryKey) == 1) {
                array_unshift($this->_select, current($primaryKey));
            } else {
                array_unshift(
                        $this->_select,
                        sprintf(
                                'CONCAT(%s)',
                                implode(',', $this->_model->getPrimaryKeyColumns())
                        )
                );
            }
        }
        if ($this->_withTimeStamp) {
            $this->_select = array_merge(
                    $this->_select,
                    ['created_on', 'updated_on', 'deleted_on']
            );
        }

        $this->_sql = sprintf('SELECT %s FROM %s',
                implode(',', array_unique($this->_select)),
                $this->_repository->getTableName()
        );
        if (isset($this->_where)) {
            $this->_sql .= ' WHERE ' . $this->_where->resolve();
            $this->_sqlValues = array_merge(
                    $this->_sqlValues,
                    $this->_where->get(true)
            );
            $this->_sql .= ' AND deleted_on ' . ($this->_withDeleted ? 'IS NOT' : 'IS') . ' NULL';
        }
        if (isset($this->_groupBy)) {
            $this->_sql .= sprintf(
                    ' GROUP BY %s',
                    implode(',', $this->_groupBy)
            );
        }
        if (isset($this->_having)) {
            $this->_sql .= ' HAVING ' . $this->_having->resolve();
            $this->_sqlValues = array_merge(
                    $this->_sqlValues,
                    $this->_having->get(true)
            );
        }
        if (isset($this->_orderBy)) {
            $this->_sql .= sprintf(
                    ' ORDER BY %s',
                    implode(',', $this->_orderBy)
            );
        }
        if (isset($this->_limit)) {
            $limitSqlString = ' LIMIT ';
            if (isset($this->_offset)) {
                $this->_sql .= sprintf($limitSqlString . '%s,%s',
                        $this->_offset,
                        $this->_limit
                );
            } else {
                $this->_sql .= sprintf($limitSqlString . '%s',
                        $this->_limit
                );
            }
        }

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
