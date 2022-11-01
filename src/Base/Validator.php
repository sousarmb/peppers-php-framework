<?php

namespace Peppers\Base;

use Peppers\Contracts\DataValidation\Validate;

abstract class Validator implements Validate {

    protected bool $hasRun = false;
    protected bool $isValid = false;
    protected string $reason;
    protected mixed $value;

    /**
     * 
     * @return mixed
     */
    public function getValue(): mixed {
        return $this->value;
    }

    /**
     * @return bool
     */
    public function hasRun(): bool {
        return $this->hasRun;
    }

    /**
     * @return bool
     */
    public function isValid(): bool {
        return $this->isValid;
    }

    /**
     * 
     * @param mixed $value
     * @return self
     */
    public function setValue(mixed $value): self {
        $this->value = $value;
        return $this;
    }

    /**
     * 
     * @return string|null
     */
    public function getReason(): ?string {
        return $this->reason;
    }

}
