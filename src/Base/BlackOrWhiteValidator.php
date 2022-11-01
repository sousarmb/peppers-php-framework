<?php

namespace Peppers\Base;

use Peppers\Base\Validator;
use Peppers\Contracts\DataValidation\ValueList;

abstract class BlackOrWhiteValidator extends Validator implements ValueList {

    protected bool $allowed = true;
    protected array $blackList;
    protected bool $forbidden = false;
    protected array $whiteList;

    /**
     * 
     * @return bool
     */
    public function isAllowed(): bool {
        return $this->allowed;
    }

    /**
     * 
     * @return bool
     */
    public function isForbidden(): bool {
        return $this->forbidden;
    }

    /**
     * 
     * @param array $values
     * @return self
     */
    public function setAllowed(array $values): self {
        $this->whiteList = $values;
        return $this;
    }

    /**
     * 
     * @param array $values
     * @return self
     */
    public function setForbidden(array $values): self {
        $this->blackList = $values;
        return $this;
    }

    /**
     * 
     * @return bool
     */
    public function isValueBlackListed(): bool {
        if (in_array($this->value, $this->blackList)) {
            $this->allowed = false;
            return $this->forbidden = true;
        }

        $this->allowed = true;
        return $this->forbidden = false;
    }

    /**
     * 
     * @return bool
     */
    public function isValueWhiteListed(): bool {
        if (in_array($this->value, $this->whiteList)) {
            $this->forbidden = false;
            return $this->allowed = true;
        }

        $this->forbidden = true;
        return $this->allowed = false;
    }

}
