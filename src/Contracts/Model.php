<?php

namespace Peppers\Contracts;

interface Model {

    /**
     * 
     * @return void
     */
    public function delete(): void;

    /**
     * 
     * @return string
     */
    public function getCreatedOn(): string;

    /**
     * 
     * @return string
     */
    public function getDeletedOn(): string;

    /**
     * 
     * @param bool|null $returnMembersOrValues
     * @return array
     */
    public function getDirtyData(?bool $returnMembersOrValues = null): array;

    /**
     * 
     * @return array
     */
    public function getModelColumns(): array;

    /**
     * 
     * @return array
     */
    public function getNonPrimaryKeyColumns(): array;

    /**
     * 
     * @param bool $returnArrayOrString
     * @param string $separator
     * @return array|string
     */
    public function getPrimaryKey(
            bool $returnArrayOrString = false,
            string $separator = ''
    ): array|string;

    /**
     * 
     * @return array
     */
    public function getPrimaryKeyColumns(): array;

    /**
     * 
     * @return array
     */
    public function getProtectedColumns(): array;

    /**
     * 
     * @return array
     */
    public function getUnprotectedColumns(): array;

    /**
     * 
     * @return string
     */
    public function getUpdatedOn(): string;

    /**
     * 
     * @param string $name
     * @return bool
     */
    public function hasColumn(string $name): bool;

    /**
     * 
     * @return bool
     */
    public function hasDirtyData(): bool;

    /**
     * 
     * @return void
     */
    public function setReadOnly(): void;

    /**
     * 
     * @return bool
     */
    public function flaggedForDelete(): bool;

    /**
     * 
     * @return bool
     */
    public function flaggedForUpdate(): bool;

    /**
     * 
     * @param bool $returnColumnsAsKeys
     * @return array
     */
    public function toArray(bool $returnColumnsAsKeys = true): array;
}
