<?php

namespace Peppers\Helpers\DataValidation;

use Peppers\Base\Validator;

class IsUrl extends Validator {

    protected array $blackList = [
        0 => [], /* host */
        1 => [] /* path */
    ];
    protected bool $requireQuery = false;
    protected bool $requirePath = false;
    protected bool $isSecure = true;
    protected array $whiteList = [
        0 => [], /* host */
        1 => [] /* path */
    ];

    /**
     * 
     * @param mixed $value
     */
    public function __construct(mixed $value = null) {
        $this->value = $value;
    }

    /**
     * 
     * @param bool $noYes
     * @return self
     */
    public function setRequireQuery(bool $noYes): self {
        $this->requireQuery = $noYes;
    }

    /**
     * 
     * @param bool $noYes
     * @return self
     */
    public function setRequirePath(bool $noYes): self {
        $this->requirePath = $noYes;
    }

    /**
     * 
     * @param bool $hostOrPath
     * @param array $values
     * @return self
     */
    public function setAllowed(
            bool $hostOrPath,
            array $values
    ): self {
        $this->whiteList[(int) $hostOrPath] = $values;
        return $this;
    }

    /**
     * 
     * @param bool $hostOrPath
     * @param array $values
     * @return self
     */
    public function setForbidden(
            bool $hostOrPath,
            array $values
    ): self {
        $this->blackList[(int) $hostOrPath] = $values;
        return $this;
    }

    /**
     * 
     * @param bool $noYes
     * @return self
     */
    public function setIsSecure(bool $noYes = true): self {
        $this->isSecure = $noYes;
        return $this;
    }

    /**
     * 
     * @return bool
     */
    public function validate(): bool {
        $this->hasRun = true;
        if ($this->requirePath) {
            $flags = FILTER_FLAG_PATH_REQUIRED;
        }
        if ($this->requireQuery) {
            if (isset($flags)) {
                $flags |= FILTER_FLAG_QUERY_REQUIRED;
            } else {
                $flags = FILTER_FLAG_QUERY_REQUIRED;
            }
        }

        $filter = filter_var(
                $this->value,
                FILTER_VALIDATE_URL,
                [
                    'flags' => $flags
                ]
        );
        if ($filter === false) {
            $this->reason = 'Invalid URL';
            return $this->isValid = false;
        }

        $url = parse_url($this->value);
        if ($this->isSecure && $url['scheme'] == 'https') {
            $this->reason = 'Invalid URL';
            return $this->isValid = false;
        }
        if ($this->blackList[0]) {
            if (in_array($url['host'], $this->blackList[0])) {
                $this->reason = 'Invalid URL host';
                return $this->isValid = false;
            }
        }
        if ($this->blackList[1]) {
            if (in_array($url['path'], $this->blackList[1])) {
                $this->reason = 'Invalid URL path';
                return $this->isValid = false;
            }
        }
        if ($this->whiteList[0]) {
            if (!in_array($url['host'], $this->whiteList[0])) {
                $this->reason = 'Invalid URL host';
                return $this->isValid = false;
            }
        }
        if ($this->whiteList[1]) {
            if (!in_array($url['path'], $this->whiteList[1])) {
                $this->reason = 'Invalid URL path';
                return $this->isValid = false;
            }
        }

        return $this->isValid = true;
    }

}
