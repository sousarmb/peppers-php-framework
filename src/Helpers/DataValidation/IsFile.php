<?php

namespace Peppers\Helpers\DataValidation;

use Peppers\Base\Validator;

class IsFile extends Validator {

    protected array $mimeType;
    protected int $minSize;
    protected int $maxSize;
    protected bool $checkIsUpload = false;

    /**
     * 
     * @param array|string|null $mimeType
     * @param string|null $filePath
     */
    public function __construct(
            array|string|null $mimeType = null,
            string|null $filePath = null,
    ) {
        if ($mimeType) {
            $this->setMimeType($mimeType);
        }

        $this->value = $filePath;
    }

    /**
     * 
     * @param bool $isUpload
     * @return self
     */
    public function checkIsUpload(bool $isUpload = true): self {
        $this->checkIsUpload = $isUpload;
        return $this;
    }

    /**
     * 
     * @param array|string $mimeType
     * @return self
     */
    public function setMimeType(array|string $mimeType): self {
        $this->mimeType = is_array($mimeType) ? $mimeType : [$mimeType];
        return $this;
    }

    /**
     * 
     * @param int|null $min
     * @param int|null $max
     * @return self
     */
    public function setSize(
            int|null $min = null,
            int|null $max = null
    ): self {
        if (!is_null($min)) {
            $this->minSize = $min;
        }
        if (!is_null($max)) {
            $this->maxSize = $max;
        }

        return $this;
    }

    /**
     * @return bool
     */
    public function validate(): bool {
        $this->hasRun = true;
        if ($this->checkIsUpload && !is_uploaded_file($this->value)) {
            $this->reason = 'Not valid upload';
            return $this->isValid = $this->value = false;
        }
        if (!is_readable($this->value)) {
            $this->reason = 'Not file or readable';
            return $this->isValid = $this->value = false;
        }
        if (!isset($this->mimeType) && !isset($this->minSize) && !isset($this->maxSize)) {
            return $this->isValid = true;
        }
        if (isset($this->mimeType)) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $this->value);
            finfo_close($finfo);
            if (!in_array($mimeType, $this->mimeType)) {
                $this->reason = 'File not acceptable';
                return $this->isValid = $this->value = false;
            }
        }

        $size = filesize($this->value);
        if (isset($this->minSize)) {
            if ($size < $this->minSize) {
                $this->reason = 'File too small';
                return $this->isValid = $this->value = false;
            }
        }
        if (isset($this->maxSize)) {
            if ($size > $this->maxSize) {
                $this->reason = 'File too big';
                return $this->isValid = $this->value = false;
            }
        }

        return $this->isValid = true;
    }

}
