<?php

namespace Peppers\Base;

use Iterator;
use JsonSerializable;
use RuntimeException;
use Peppers\Contracts\Model as ModelContract;

abstract class Model implements Iterator, JsonSerializable, ModelContract {
    /* these members MUST be set in the repository class that extends the base
     * repository */

//    protected array $protectedColumns = ['created_on', 'deleted_on', 'updated_on'];
//    protected array $columns;
//    protected array $primaryKeyColumns;
    protected array $modelData = [];
    protected array $dirtyModelData = [];
    protected bool $deleteFlag = false;
    protected bool $readOnly = false;

    /**
     * $data must be a key => value map where the keys are the set or a subset
     * of the model members
     * 
     * @param array|null $modelData
     */
    public function __construct(?array $modelData = null) {
        if (!is_null($modelData)) {
            foreach ($modelData as $key => $value) {
                if (in_array($key, $this->columns)) {
                    $this->modelData[$key] = $value;
                }
            }
        }
    }

    /**
     * 
     * @return void
     */
    public function delete(): void {
        $this->deleteFlag = true;
    }

    /**
     * 
     * @param string $name
     * @return mixed
     * @throws RuntimeException
     */
    public function __get(string $name): mixed {
        if (array_key_exists($name, $this->dirtyModelData)) {
            return $this->dirtyModelData[$name];
        }
        if (array_key_exists($name, $this->modelData)) {
            return $this->modelData[$name];
        }

        throw new RuntimeException('Unknown or unset model property ' . $name . ' in ' . self::class);
    }

    /**
     * 
     * @param string $name
     * @param mixed $value
     * @return void
     * @throws RuntimeException
     */
    public function __set(
            string $name,
            mixed $value
    ): void {
        if ($this->readOnly) {
            throw new RuntimeException('Model instance set to read-only');
        } elseif ($this->flaggedForDelete()) {
            throw new RuntimeException('Model set to be deleted');
        } elseif (array_key_exists($name, $this->protectedColumns)) {
            throw new RuntimeException('Cannot set protected model property');
        }
        if (array_key_exists($name, $this->modelData) && $this->modelData[$name] !== $value) {
            $this->dirtyModelData[$name] = $value;
        } else {
            // probably new model instance or creating the instance with PDO
            $this->modelData[$name] = $value;
        }
    }

    /**
     * 
     * @return string
     */
    public function getCreatedOn(): string {
        return $this->modelData['created_on'];
    }

    /**
     * 
     * @return string
     */
    public function getDeletedOn(): string {
        return $this->modelData['deleted_on'];
    }

    /**
     * 
     * @param bool|null $returnMembersOrValues  FALSE = get dirty member names
     *                                          TRUE  = get dirty values
     *                                          NULL  = get dirty member names + values
     * @return array
     */
    public function getDirtyData(?bool $returnMembersOrValues = null): array {
        if (is_null($returnMembersOrValues)) {
            return $this->dirtyModelData;
        }

        return $returnMembersOrValues ? array_values($this->dirtyModelData) : array_keys($this->dirtyModelData);
    }

    /**
     * 
     * @return array
     */
    public function getModelColumns(): array {
        return $this->columns;
    }

    /**
     * 
     * @param bool $returnArrayOrString
     * @return array|string
     */
    public function getPrimaryKey(
            bool $returnArrayOrString = false,
            string $separator = ''
    ): array|string {
        $primaryKeys = array_map(
                fn($key) => $this->modelData[$key],
                array_intersect(
                        $this->primaryKeyColumns,
                        $this->columns
                )
        );
        return $returnArrayOrString ? implode($separator, $primaryKeys) : $primaryKeys;
    }

    /**
     * Get the names of primary key columns
     * 
     * @return array
     */
    public function getPrimaryKeyColumns(): array {
        return $this->primaryKeyColumns;
    }

    /**
     * 
     * @return array
     */
    public function getNonPrimaryKeyColumns(): array {
        return array_diff($this->getModelColumns(), $this->getPrimaryKeyColumns());
    }

    /**
     * Get the names of protected (read-only) columns
     * 
     * @return array
     */
    public function getProtectedColumns(): array {
        return $this->protectedColumns;
    }

    /**
     * Get the names of unprotected (read-write) columns
     * 
     * @return array
     */
    public function getUnprotectedColumns(): array {
        return array_diff($this->columns, $this->protectedColumns);
    }

    /**
     * 
     * @return string
     */
    public function getUpdatedOn(): string {
        return $this->modelData['updated_on'];
    }

    /**
     * 
     * @return void
     */
    public function setReadOnly(): void {
        $this->readOnly = true;
    }

    /**
     * Model set to be deleted?
     * 
     * @return bool
     */
    public function flaggedForDelete(): bool {
        return $this->deleteFlag;
    }

    /**
     * Model set for update? (has dirty data)
     * 
     * @return bool
     */
    public function flaggedForUpdate(): bool {
        return (bool) $this->dirtyModelData;
    }

    /**
     * 
     * @param string $name
     * @return bool
     */
    public function hasColumn(string $name): bool {
        return in_array($name, $this->columns);
    }

    /**
     * 
     * @return bool
     */
    public function hasDirtyData(): bool {
        return empty($this->dirtyModelData) ? false : true;
    }

    /**
     * 
     * @param bool $returnColumnsAsKeys If TRUE return associative array with  
     *                                  model properties for indexes. If FALSE 
     *                                  return model properties as numerically 
     *                                  indexed array
     * @return array
     */
    public function toArray(bool $returnColumnsAsKeys = true): array {
        $array = array_merge(
                $this->modelData,
                $this->dirtyModelData
        );
        return $returnColumnsAsKeys ? $array : array_values($array);
    }

    /**
     * Alias of toArray()
     * 
     * @return mixed
     */
    public function jsonSerialize(): mixed {
        return $this->toArray();
    }

    /**
     * 
     * @return void
     */
    public function rewind(): void {
        reset($this->modelData);
    }

    /**
     * 
     * @return mixed
     */
    public function current(): mixed {
        $modelDataKey = key($this->modelData);
        if (array_key_exists($modelDataKey, $this->dirtyModelData)) {
            return $this->dirtyModelData[$modelDataKey];
        }

        return current($this->modelData);
    }

    /**
     * 
     * @return int|string|null
     */
    public function key(): int|string|null {
        return key($this->modelData);
    }

    /**
     * 
     * @return void
     */
    public function next(): void {
        next($this->modelData);
    }

    /**
     * 
     * @return bool
     */
    public function valid(): bool {
        return (bool) key($this->modelData);
    }

}
