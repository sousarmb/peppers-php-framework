<?php

namespace Peppers\Contracts;

use Generator;
use Peppers\Contracts\Model;
use Peppers\Helpers\Sql\DataPromise;
use Peppers\Helpers\Sql\DeletePromise;

interface ModelRepository {

    /**
     * 
     * @return Model
     */
    public function create(): Model;

    /**
     * 
     * @return DeletePromise
     */
    public function deleteByCondition(): DeletePromise;

    /**
     * 
     * @param array $primaryKeyValues
     * @return int
     */
    public function deleteByPrimaryKey(array $primaryKeyValues): int;

    /**
     * 
     * @param string|null $store
     * @return void
     */
    public function erase(?string $store = null): void;

    /**
     * 
     * @return DataPromise
     */
    public function findByCondition(): DataPromise;

    /**
     * 
     * @param array $primaryKeyValues
     * @param array|null $columns
     * @return Model|null
     */
    public function findByPrimaryKey(
            array $primaryKeyValues,
            ?array $columns = null
    ): ?Model;

    /**
     * 
     * @param bool $stopOnFirstFail
     * @return array|int|Model
     */
    public function flushCreates(bool $stopOnFirstFail = false): array|int|Model;

    /**
     * 
     * @param bool $stopOnFirstFail
     * @return array|int|Model
     */
    public function flushDeletes(bool $stopOnFirstFail = false): array|int|Model;

    /**
     * 
     * @param bool $stopOnFirstFail
     * @return array|int|Model
     */
    public function flushUpdates(bool $stopOnFirstFail = false): array|int|Model;

    /**
     * 
     * @param bool $returnKeysOrStore
     * @return array
     */
    public function getAll(bool $returnKeysOrStore = false): array;

    /**
     * 
     * @return mixed
     */
    public function getConnection(): mixed;

    /**
     * 
     * @param bool $returnQualifiedOrUnqualified
     * @return string
     */
    public function getModelClass(bool $returnQualifiedOrUnqualified = false): string;

    /**
     * 
     * @return string
     */
    public function getTableName(): string;

    /**
     * 
     * @param DataPromise $promise
     * @return Generator
     */
    public function resolveDataPromise(DataPromise $promise): Generator;

    /**
     * 
     * @param DeletePromise $promise
     * @return int
     */
    public function resolveDeletePromise(DeletePromise $promise): int;
}
