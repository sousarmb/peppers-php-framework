<?php

namespace Peppers\Base;

use Generator;
use Iterator;
use Peppers\Contracts\Model;
use Peppers\Contracts\ModelRepository as ModelRepositoryContract;
use Peppers\Contracts\Promise;
use Peppers\Helpers\Arrays;
use Peppers\Helpers\Sql\Conditions;
use Peppers\Helpers\Sql\DataPromise;
use Peppers\Helpers\Sql\DeletePromise;
use Peppers\Helpers\Types\Operator;
use Peppers\Contracts\ConnectionManager;
use Peppers\ServiceLocator;
use PDO;
use RuntimeException;

abstract class ModelRepository implements Iterator, ModelRepositoryContract {
    /* these members MUST be set in the repository class that extends the base
     * repository */

//    protected Model $model;
//    protected string $table;
    protected array $local = [];
    protected array $create = [];
    protected PDO $conn;
    protected $credentials;
    protected $datasource;
    protected string $primaryKeyForPreparedStatement;
    protected string $modelClass;

    public function __construct(
            string $dotNotationCredentials = 'default',
            string $dotNotationDataSource = 'default'
    ) {
        $this->credentials = $dotNotationCredentials;
        $this->datasource = $dotNotationDataSource;

        $primaryKey = $this->model->getPrimaryKeyColumns();
        $this->primaryKeyForPreparedStatement = count($primaryKey) == 1 ? current($primaryKey) . '=?' : implode(' AND ',
                        array_map(
                                function ($column) {
                                    return $column .= '=?';
                                },
                                $this->model->getPrimaryKeyColumns()
                        )
        );
        $this->modelClass = $this->model::class;
    }

    /**
     * 
     * @param Model $model
     * @return self
     * @throws RuntimeException
     */
    public function pushNew(Model $model): self {
        if ($model instanceof $this->modelClass) {
            $this->create[] = $model;
            return $this;
        }

        throw new RuntimeException('Repository ' . self::class . ' does not accept instances of ' . $this->modelClass);
    }

    /**
     * 
     * @return Model
     */
    public function create(): Model {
        $this->create[] = clone $this->model;
        return end($this->create);
    }

    /**
     * Delete local or remote model instances based on Promise, which takes 
     * a Condition object, where you can set the conditions for a model to be
     * (soft) deleted from the repository
     * 
     * @return DeletePromise
     */
    public function deleteByCondition(): DeletePromise {
        return new DeletePromise($this);
    }

    /**
     * Search local stores for the given Model and flags it for deletion. If 
     * not found SOFT DELETEs from database to prevent future SELECTion
     * 
     * @param array $primaryKeyValues
     * @return int
     */
    public function deleteByPrimaryKey(array $primaryKeyValues): int {
        $primaryKey = implode('', $primaryKeyValues);
        if (array_key_exists($primaryKey, $this->local)) {
            // flag for deletion
            $this->local[$primaryKey]->delete();
            return 1;
        }
        // "delete" even if not in local store
        $this->setupConnection();
        $sql = sprintf('UPDATE %s SET deleted_on=NOW() WHERE %s AND deleted_on IS NULL',
                $this->table,
                $this->primaryKeyForPreparedStatement
        );
        $pstmt = $this->conn->prepare($sql);
        $pstmt->execute($primaryKeyValues);
        return $pstmt->rowCount();
    }

    /**
     * Find model instances in local or remote repositories with the use of a
     * Promise, which is resolved using data source. Local model stores are 
     * checked to prevent fetching records from the data source that were 
     * previously fetched.
     * 
     * Loaded model instances  are updated if not flagged for deletion or 
     * update.
     * 
     * @return DataPromise
     */
    public function findByCondition(): DataPromise {
        return new DataPromise(
                $this,
                $this->model
        );
    }

    /**
     * Search local stores for the given Model, if not found query data source 
     * and update local stores
     * 
     * @param array $primaryKeyValues You MUST pass as many values as there 
     *                                are primary key columns
     * @param array|null $columns     Columns to be retrieved from the database,
     *                                if NULL then all mode columns are SELECTed
     * @return Model|null
     */
    public function findByPrimaryKey(
            array $primaryKeyValues,
            ?array $columns = null
    ): ?Model {
        $storeKey = implode('', $primaryKeyValues);
        if (array_key_exists($storeKey, $this->local)) {
            return $this->local[$storeKey];
        }

        $this->setupConnection();
        $sql = sprintf('SELECT %s FROM %s WHERE %s AND deleted_on IS NULL',
                implode(',', $columns ?? $this->model->getModelColumns()),
                $this->table,
                $this->primaryKeyForPreparedStatement
        );
        $pstmt = $this->conn->prepare($sql);
        if (!$pstmt->execute($primaryKeyValues)) {
            return null;
        }

        $model = $pstmt->fetchObject($this->model::class);
        if (!$model) {
            return null;
        }

        $this->local[$storeKey] = $model;
        return $model;
    }

    /**
     * Get all the models in the local repository. Does not fetch from the 
     * database.
     * 
     * @param bool $returnKeysOrStore
     * @return array
     */
    public function getAll(bool $returnKeysOrStore = false): array {
        return $returnKeysOrStore ? $this->local : array_keys($this->local);
    }

    /**
     * 
     * @return string
     */
    public function getTableName(): string {
        return $this->table;
    }

    /**
     * 
     * @param bool $returnQualifiedOrUnqualified
     * @return string
     */
    public function getModelClass(bool $returnQualifiedOrUnqualified = false): string {
        if ($returnQualifiedOrUnqualified) {
            $unqualified = explode('\\', $this->modelClass);
            return end($unqualified);
        }

        return $this->modelClass;
    }

    /**
     * 
     * @return void
     */
    private function setupConnection(): void {
        if (isset($this->conn)) {
            return;
        }

        $this->conn = ServiceLocator::get(ConnectionManager::class)
                ->connectTo(
                $this->credentials,
                $this->datasource
        );
    }

    /**
     * Return underlying connection to remote model repository
     * 
     * @return PDO
     */
    public function getConnection(): PDO {
        if (!isset($this->conn)) {
            $this->setupConnection();
        }

        return $this->conn;
    }

    /**
     * Delivers on promises.
     * 
     * Data promises get data from the database, EXCEPT FOR models marked for 
     * delete and update. Local repository models matching WHERE conditions are
     * merged to the return result set. 
     * 
     * IMPORTANT: if the DataPromise uses GROUP BY results are returned from 
     * the database "as is", no cross checking with local repository. This 
     * is because there is no (implemented) way of checking the primary keys 
     * for models.
     * 
     * @param Promise $promise
     * @return Generator
     */
    public function resolveDataPromise(DataPromise $promise): Generator {
        foreach ($promise->hasGrouping() ? $this->getGroupedDataForPromise($promise) : $this->getDataForPromise($promise) as $key => $model) {
            yield $key => $model;
        }
    }

    /**
     * 
     * @param DataPromise $promise
     * @return Generator
     * @throws RuntimeException
     */
    private function getGroupedDataForPromise(DataPromise $promise): Generator {
        $conditions = $promise->getWhere();
        if (is_null($conditions)) {
            throw new RuntimeException(
                            /* actually you can but it's not allowed in peppers */
                            'Unconditional SELECT from repository not allowed in ' . self::class
            );
        }

        $this->setupConnection();
        $pstmt = $this->conn->prepare($promise->getSql());
        $pstmt->execute($promise->getSqlValues());
        foreach ($pstmt->fetchAll(
                PDO::FETCH_CLASS,
                $this->model::class
        ) as $key => $modelInstance) {
            $modelInstance->setReadOnly();
            yield $key => $modelInstance;
        }
    }

    /**
     * 
     * @param string $filterForEval
     * @return array
     */
    private function dontSelectThese(string $filterForEval): array {
        return array_filter(
                $this->local,
                function ($model) use ($filterForEval) {
                    if ($model->flaggedForDelete()) {
                        return true;
                    }
                    // ... and that match (or not) the developer CONDITIONs
                    return eval("return ($filterForEval);");
                }
        );
    }

    /**
     * 
     * @param DataPromise $promise
     * @return Generator
     * @throws RuntimeException
     */
    private function getDataForPromise(DataPromise $promise): Generator {
        $conditions = $promise->getWhere();
        if (is_null($conditions)) {
            throw new RuntimeException(
                            /* actually you can but it's not allowed in peppers */
                            'Unconditional SELECT from repository not allowed in ' . self::class
            );
        }
        /* go through local repository and find models that the developer 
         * wants deleted so they don't get SELECTed but might be shown in the 
         * result set */
        if ($this->local) {
            $dontSelectThese = $this->dontSelectThese(
                    $this->getFilterForEval($conditions)
            );
            if ($dontSelectThese) {
                /* add new condition which will have the primary key for local 
                 * repository model instance not be SELECTed */
                $preventSelect = new Conditions();
                foreach ($dontSelectThese as $model) {
                    foreach ($this->model->getPrimaryKeyColumns() as $column) {
                        $preventSelect->where($column, Operator::neq, $model->$column);
                    }
                }
                $conditions->unshift($preventSelect);
            }
        }
        // get the data
        $this->setupConnection();
        $pstmt = $this->conn->prepare($promise->getSql());
        $pstmt->execute($promise->getSqlValues());
        /* get the rows (model instance) from the database and add to 
         * local repository. Don't select models present in local 
         * repository or marked for update */
        $allModels = $pstmt->fetchAll(
                PDO::FETCH_GROUP | PDO::FETCH_UNIQUE | PDO::FETCH_CLASS,
                $this->model::class
        );
        // add to local store
        $this->local = array_merge($this->local, $allModels);
        // prepare the record set with remote and local data
        if (isset($dontSelectThese)) {
            $allModels = array_merge($allModels, $dontSelectThese);
        }
        $orderBy = $promise->getOrderBy();
        if (!is_null($orderBy)) {
            // do some sorting to not "destroy" the order the developer set
            $memberDirection = [];
            foreach ($orderBy as $orderClause) {
                list($column, $direction) = explode(' ', $orderClause);
                $memberDirection[] = [$column, $direction];
            }
            // sort the model collection
            Arrays::sortObjectCollectionByMemberAndDirection(
                    $allModels,
                    $memberDirection
            );
        }
        // return the data set
        foreach ($allModels as $key => $modelInstance) {
            yield $key => $modelInstance;
        }
    }

    /**
     * Mark models in local data repository for deletion and SOFT DELETE rows 
     * in the database. If the local repository has any instances marked for 
     * deletion the repository must be flushed with flushDelete()
     * 
     * @param DeletePromise $promise
     * @return int
     * @throws RuntimeException
     */
    public function resolveDeletePromise(DeletePromise $promise): int {
        $conditions = $promise->getWhere();
        if (is_null($conditions)) {
            throw new RuntimeException(
                            /* actually you can but it's not allowed in peppers */
                            'Unconditional DELETE from repository not allowed in ' . self::class
            );
        }

        $filterForEval = $this->getFilterForEval($conditions);
        /* go through local repository and find models that the developer 
         * wants deleted */
        array_walk($this->local,
                function ($model) use ($filterForEval) {
                    if ($model->flaggedForDelete()) {
                        return;
                    }
                    if (eval("return ($filterForEval);")) {
                        $model->delete();
                    }
                }
        );
        // (soft) DELETE rows in the database
        $this->setupConnection();
        $pstmt = $this->conn->prepare($promise->getSql());
        $pstmt->execute($promise->getSqlValues());
        return $pstmt->rowCount();
        /* models in the local repository are deleted in the end of the script
          so the developer can use (but not update) their data if needed */
    }

    /**
     * 
     * @param array|bool|float|int|string $value
     * @return string
     */
    private function getTypeSpecifierForReplacement(array|bool|float|int|string &$value): string {
        if (is_array($value)) {
            $otherReplace = [];
            foreach ($value as $otherValue) {
                $otherReplace[] = $this->getTypeSpecifierForReplacement($otherValue);
            }
            $replace = implode(',', $otherReplace);
        } elseif (is_bool($value)) {
            $replace = '%b';
        } elseif (is_float($value)) {
            $replace = '%f';
        } elseif (is_int($value)) {
            $replace = '%u';
        } else {
            // string
            $replace = "'%s'";
        }

        return $replace;
    }

    /**
     * 
     * @param Conditions $conditions
     * @return string
     */
    private function getFilterForEval(Conditions $conditions): string {
        // get the WHERE clause of the statement string to make changes to it
        $conditionString = $conditions->resolve();
        /* get the values the developer set for the conditions, check its 
         * types and change the condition string accordingly */
        foreach ($conditions->get(true) as $value) {
            $replace = $this->getTypeSpecifierForReplacement($value);
            // make sure types are checked 
            $conditionString = preg_replace(
                    '/\?/',
                    $replace,
                    $conditionString,
                    1
            );
        }
        // as well and ISO SQL is translated to PHP
        $conditionString = /* preg_replace( */str_replace(
                ['/ = /', '/ <> /'],
                [' === ', ' != '],
                $conditionString
        );
        // "replace" column names with model members
        $conditionString = '$model->' . $conditionString;
        foreach ($this->model->getModelColumns() as $column) {
            $conditionString = /* preg_replace */str_replace(" $column",
                    " \$model->$column",
                    $conditionString
            );
        }
        // come together: model members and its value
        return vsprintf(
                $conditionString,
                Arrays::flatten($conditions->get(true))
        );
    }

    /**
     * Create model instances in the database
     * 
     * @param bool $stopOnFirstFail
     * @return array|int|Model  [Model, Model, ...] of failed to 
     *                          create instances. Integer if all model instances 
     *                          created in the database successfully. Model 
     *                          instance that failed to created if 
     *                          $stopOnFirstFail set to TRUE.
     */
    public function flushCreates(bool $stopOnFirstFail = false): array|int|Model {
        $this->setupConnection();
        if (!$this->create) {
            return 0;
        }
        // ... leave out the protected columns
        $columns = array_diff(
                $this->model->getModelColumns(),
                $this->model->getProtectedColumns()
        );
        $placeholders = implode(
                ',',
                array_fill(0, count($columns), '?')
        );
        // newly created objects
        $sql = sprintf('INSERT INTO %s (%s) VALUES (%s)',
                $this->getTableName(),
                implode(',', $columns),
                $placeholders,
        );
        $pstmt = $this->conn->prepare($sql);
        $this->conn->beginTransaction();
        $failed = [];
        foreach ($this->create as $key => $model) {
            if ($pstmt->execute($model->toArray(false))) {
                // clear local repository
                unset($this->create[$key]);
            } elseif ($stopOnFirstFail) {
                $this->conn->rollBack();
                return $model;
            } else {
                $failed[] = $model;
            }
        }
        $this->conn->commit();
        return $failed ?: $pstmt->rowCount();
    }

    /**
     * Soft delete model instances in the database
     * 
     * @param bool $stopOnFirstFail
     * @return array|int|Model  [Model, Model, ...] of failed to 
     *                          create instances. Integer if all model instances 
     *                          created in the database successfully. Model 
     *                          instance that failed to created if 
     *                          $stopOnFirstFail set to TRUE.
     */
    public function flushDeletes(bool $stopOnFirstFail = false): array|int|Model {
        $this->setupConnection();
        if (!$this->local) {
            return 0;
        }

        $deleteThese = array_filter(
                $this->local,
                function ($model) {
                    return $model->flaggedForDelete();
                }
        );
        if (!$deleteThese) {
            return 0;
        }

        $sql = sprintf('UPDATE %s SET deleted_on=NOW() WHERE %s',
                $this->getTableName(),
                $this->primaryKeyForPreparedStatement
        );
        $this->conn->beginTransaction();
        $pstmt = $this->conn->prepare($sql);
        $failed = [];
        foreach ($deleteThese as $primaryKey => $model) {
            if ($pstmt->execute($model->getPrimaryKey())) {
                // clear local repository
                unset($this->local[$primaryKey]);
            } elseif ($stopOnFirstFail) {
                $this->conn->rollBack();
                return $model;
            } else {
                $failed[] = $model;
            }
        }
        $this->conn->commit();
        return $failed ?: $pstmt->rowCount();
    }

    /**
     * Update model instances in the database
     * 
     * @param bool $stopOnFirstFail
     * @return array|int|Model  [Model, Model, ...] of failed to 
     *                          create instances. Integer if all model instances 
     *                          created in the database successfully. Model 
     *                          instance that failed to created if 
     *                          $failOnFirstFail set to TRUE.
     */
    public function flushUpdates(bool $stopOnFirstFail = false): array|int|Model {
        $this->setupConnection();
        if (!$this->local) {
            return 0;
        }

        $updateThese = array_filter($this->local, function ($model) {
            return $model->flaggedForUpdate();
        });
        if (!$updateThese) {
            return 0;
        }

        $sql = sprintf('UPDATE %s SET updated_on=NOW(),%s WHERE %s',
                $this->getTableName(),
                '%s', /* here go the columns to be updated */
                $this->primaryKeyForPreparedStatement
        );
        $this->conn->beginTransaction();
        $failed = [];
        foreach ($updateThese as $primaryKey => $model) {
            $updateClause = array_map(
                    fn($member) => $member . '=?',
                    $model->getDirtyData(false)
            );
            // enter the updated columns
            $update = sprintf($sql, implode(',', $updateClause));
            $pstmt = $this->conn->prepare($update);
            if ($pstmt->execute(
                            array_merge(
                                    $model->getDirtyData(true),
                                    $model->getPrimaryKey()
                            )
                    )
            ) {
                // flush from local repository
                unset($this->local[$primaryKey]);
            } elseif ($stopOnFirstFail) {
                $this->conn->rollBack();
                return $model;
            } else {
                $failed[] = $model;
            }
        }
        $this->conn->commit();
        return $failed ?: $pstmt->rowCount();
    }

    /**
     * Delete models in repository stores, DOES NOT FLUSH to data source
     * 
     * @param string|null $store    NULL clears ALL repository stores
     * @return void
     * @throws RuntimeException
     */
    public function erase(?string $store = null): void {
        if (is_null($store)) {
            $this->create = $this->local = [];
            return;
        }
        switch ($store) {
            case 'create':
            case 'store':
                $this->$store = [];
                return;
            default:
                throw new RuntimeException("Unknown store $store in repository" . __CLASS__);
        }
    }

    /**
     * 
     * @return void
     */
    public function rewind(): void {
        reset($this->local);
    }

    /**
     * 
     * @return mixed
     */
    public function current(): mixed {
        $model = current($this->local);
        if ($model->flaggedForDelete()) {
            $this->next();
            return $this->current();
        } else {
            return $model;
        }
    }

    /**
     * 
     * @return int|string|null
     */
    public function key(): int|string|null {
        return key($this->local);
    }

    /**
     * 
     * @return void
     */
    public function next(): void {
        next($this->local);
    }

    /**
     * 
     * @return bool
     */
    public function valid(): bool {
        return (bool) key($this->local);
    }

}
