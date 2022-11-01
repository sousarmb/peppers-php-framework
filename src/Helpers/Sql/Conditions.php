<?php

namespace Peppers\Helpers\Sql;

use Peppers\Helpers\Types\Operator;

class Conditions {

    private array $_conditions;
    private array $_values = [];

    /**
     * 
     * @return bool
     */
    public function hasConditions(): bool {
        return isset($this->_conditions);
    }

    /**
     * 
     * @return string|null
     */
    public function resolve(): ?string {
        if (!isset($this->_conditions)) {
            return null;
        }
        /* this method is called multiple times so $values[] better be reset
         * everytime */
        $this->_values = [];
        return $this->innerResolve(
                        $this->_conditions,
                        $this->_values
        );
    }

    /**
     * 
     * @param array $conditions
     * @param array $values
     * @return string
     */
    private function innerResolve(
            array $conditions,
            array &$values
    ): string {
        $forQuery = '';
        foreach ($conditions as $condition) {
            if ($condition[0] instanceof Conditions) {
                if (strlen($forQuery)) {
                    $forQuery .= isset($condition[1]) ? ' OR ' : ' AND ';
                }

                $forQuery .= '( ' . $this->innerResolve($condition[0]->get(false), $values) . ' )';
            } else {
                if (strlen($forQuery)) {
                    $forQuery .= isset($condition[3]) ? ' OR ' : ' AND ';
                }
                if (!isset($condition[1]) && !isset($condition[2])) {
                    $forQuery .= sprintf('%s', $condition[0]);
                } else {
                    $forQuery .= sprintf('%s%s?',
                            $condition[0],
                            $condition[1]
                    );
                    $values[] = $condition[2];
                }
            }
        }
        return $forQuery;
    }

    /**
     * 
     * @param string $column
     * @param bool|int|float|string $startValue
     * @param bool|int|float|string $endValue
     * @return self
     */
    public function between(
            string $column,
            bool|int|float|string $startValue,
            bool|int|float|string $endValue
    ): self {
        $this->andCondition()
                ->where($column, Operator::gte, $startValue)
                ->andWhere($column, Operator::lte, $endValue);
        return $this;
    }

    /**
     * 
     * @param string $column
     * @param bool|int|float|string $startValue
     * @param bool|int|float|string $endValue
     * @return self
     */
    public function andBetween(
            string $column,
            bool|int|float|string $startValue,
            bool|int|float|string $endValue
    ): self {
        return $this->between($column, $startValue, $endValue);
    }

    /**
     * 
     * @param string $column
     * @param bool|int|float|string $startValue
     * @param bool|int|float|string $endValue
     * @return self
     */
    public function orBetween(
            string $column,
            bool|int|float|string $startValue,
            bool|int|float|string $endValue
    ): self {
        $this->orCondition()
                ->where($column, Operator::gte, $startValue)
                ->andWhere($column, Operator::lte, $endValue);
        return $this;
    }

    /**
     * 
     * @param string $column
     * @param Operator $operator
     * @param bool|int|float|string $value
     * @return self
     */
    public function where(
            string $column,
            Operator $operator,
            bool|int|float|string $value
    ): self {
        $this->_conditions[] = [
            $column,
            $this->resolveOperator($operator),
            $value
        ];
        return $this;
    }

    /**
     * 
     * @param string $column
     * @param Operator $operator
     * @param bool|int|float|string $value
     * @return self
     */
    public function andWhere(
            string $column,
            Operator $operator,
            bool|int|float|string $value
    ): self {
        return $this->where(
                        $column,
                        $operator,
                        $value
        );
    }

    /**
     * 
     * @param string $column
     * @param Operator $operator
     * @param bool|int|float|string $value
     * @return self
     */
    public function orWhere(
            string $column,
            Operator $operator,
            bool|int|float|string $value
    ): self {
        $this->_conditions[] = [
            $column,
            $this->resolveOperator($operator),
            $value,
            'or'
        ];
        return $this;
    }

    /**
     * 
     * @param string $functionCall Use with care: no parsing or validation is 
     *                             performed made on this string!
     * @param Operator|null $operator
     * @param bool|int|float|string|null $value
     * @return self
     */
    public function function(
            string $functionCall,
            ?Operator $operator = null,
            bool|int|float|string|null $value = null
    ): self {
        if (is_null($operator) && is_null($value)) {
            $this->_conditions[] = [$functionCall];
        } else {
            $this->_conditions[] = [
                $functionCall,
                is_null($operator) ? $operator : $this->resolveOperator($operator),
                $value
            ];
        }
        return $this;
    }

    /**
     * 
     * @param string $functionCall Use with care: no parsing or validation is 
     *                             performed made on this string!
     * @param Operator|null $operator
     * @param bool|int|float|string|null $value
     * @return self
     */
    public function andFunction(
            string $functionCall,
            ?Operator $operator = null,
            bool|int|float|string|null $value = null
    ): self {
        $this->function(
                $functionCall,
                $operator,
                $value
        );
        return $this;
    }

    /**
     * 
     * @param string $functionCall Use with care: no parsing or validation is 
     *                             performed made on this string!
     * @param Operator|null $operator
     * @param bool|int|float|string|null $value
     * @return self
     */
    public function orFunction(
            string $functionCall,
            ?Operator $operator = null,
            bool|int|float|string|null $value = null
    ): self {
        if (is_null($operator) && is_null($value)) {
            $this->_conditions[] = [$functionCall];
        } else {
            $this->_conditions[] = [
                $functionCall,
                is_null($operator) ? $operator : $this->resolveOperator($operator),
                $value,
                'or'
            ];
        }

        return $this;
    }

    /**
     * 
     * @return Conditions
     */
    public function andCondition(): Conditions {
        $condition = new self();
        $this->_conditions[] = [$condition];
        return $condition;
    }

    /**
     * 
     * @param string $column
     * @param array $values
     * @return self
     */
    public function whereIn(
            string $column,
            array $values
    ): self {
        $condition = new self();
        foreach ($values as $value) {
            $condition->orWhere($column, Operator::eq, $value);
        }
        $this->_conditions[] = [$condition];
        return $this;
    }

    /**
     * 
     * @param string $column
     * @param array $values
     * @return self
     */
    public function whereNotIn(
            string $column,
            array $values
    ): self {
        $condition = new self();
        foreach ($values as $value) {
            $condition->where($column, Operator::neq, $value);
        }
        $this->_conditions[] = [$condition];
        return $this;
    }

    /**
     * Alias for whereIn()
     * 
     * @param string $column
     * @param array $values
     * @return self
     */
    public function andWhereIn(
            string $column,
            array $values
    ): self {
        return $this->whereIn($column, $values);
    }

    /**
     * Alias for whereNotIn()
     * 
     * @param string $column
     * @param array $values
     * @return self
     */
    public function andWhereNotIn(
            string $column,
            array $values
    ): self {
        return $this->whereNotIn($column, $values);
    }

    /**
     * 
     * @return Conditions
     */
    public function orCondition(): Conditions {
        $condition = new self();
        $this->_conditions[] = [$condition, 'or'];
        return $condition;
    }

    /**
     * 
     * @param string $column
     * @param array $values
     * @return self
     */
    public function orWhereIn(
            string $column,
            array $values
    ): self {
        $condition = new self();
        foreach ($values as $value) {
            $condition->orWhere($column, Operator::eq, $value);
        }
        $this->_conditions[] = [$condition, 'or'];
        return $this;
    }

    /**
     * 
     * @param string $column
     * @param array $values
     * @return self
     */
    public function orWhereNotIn(
            string $column,
            array $values
    ): self {
        $condition = new self();
        foreach ($values as $value) {
            $condition->where($column, Operator::neq, $value);
        }
        $this->_conditions[] = [$condition, 'or'];
        return $this;
    }

    /**
     * 
     * @param bool $returnConditionsOrValues
     * @return array
     */
    public function get(bool $returnConditionsOrValues = true): array {
        return $returnConditionsOrValues ? $this->_values : $this->_conditions;
    }

    /**
     * 
     * @param Operator $operator
     * @return string
     */
    private function resolveOperator(Operator $operator): string {
        switch ($operator) {
            case $operator::eq:
                return ' = ';
            case $operator::gt:
                return ' > ';
            case $operator::gte:
                return ' >= ';
            case $operator::lt:
                return ' < ';
            case $operator::lte:
                return ' <= ';
            case $operator::neq:
                return ' <> ';
        }
    }

    /**
     * Put Conditions instance first
     * 
     * @param Conditions $conditions
     * @return self
     */
    public function unshift(Conditions $conditions): self {
        array_unshift($this->_conditions, [$conditions]);
        return $this;
    }

}
