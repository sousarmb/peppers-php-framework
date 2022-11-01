<?php

namespace Peppers\Helpers\Http\Request;

use Peppers\Base\RequestParameter;
use Peppers\Helpers\Arrays;
use Peppers\Helpers\UploadedFile;

class FileParameter extends RequestParameter {

    private array|null $_fileData;
    private UploadedFile $_file;

    /**
     * 
     * @param string $parameterNameOrDotNotation
     */
    public function __construct(
            string $parameterNameOrDotNotation
    ) {
        if (false === strpos($parameterNameOrDotNotation, '.')) {
            if (array_key_exists($parameterNameOrDotNotation, $_FILES)) {
                $this->_fileData = & $_FILES[$parameterNameOrDotNotation];
            } else {
                $this->_fileData = null;
            }
        } else {
            $this->_fileData = Arrays::getFrom(
                            $_FILES,
                            $parameterNameOrDotNotation
            );
        }

        $this->name = $parameterNameOrDotNotation;
        $this->value = is_null($this->_fileData) ? null : $this->_fileData['tmp_name'];
    }

    /**
     * 
     * @return string
     */
    public function getMimeType(): string {
        return $this->_fileData['type'];
    }

    /**
     * 
     * @return int
     */
    public function getSize(): int {
        return $this->_fileData['size'];
    }

    /**
     *
     * @return string
     */
    public function getName(): string {
        return $this->name;
    }

    /**
     * 
     * @return mixed
     */
    public function getValue(): mixed {
        return $this->value;
    }

    /**
     * Alias for getValue()
     * 
     * @return string
     */
    public function getPath(): mixed {
        return $this->getValue();
    }

    /**
     * Warning: file is checked with is_uploaded_file(), no other validation is 
     * performed on the file. File is opened in read-only mode and only once, 
     * you'll always get the same file instance, not a clone.
     * 
     * @return UploadedFile
     */
    public function getFile(): UploadedFile {
        if (isset($this->_file)) {
            return $this->_file;
        }
        if (!is_uploaded_file($this->value)) {
            throw new RuntimeException('Invalid upload file ' . $this->getName());
        }

        return $this->_file = new UploadedFile(
                $this->name,
                'r'
        );
    }

    /**
     * 
     * @return string
     */
    public function getFileName(): string {
        return $this->_fileData['name'];
    }

    /**
     * 
     * @return string
     */
    public function __toString(): string {
        return $this->getName();
    }

    /**
     * 
     * @return object   But it's not the file itself, just filename => __CLASS__
     */
    public function jsonSerialize(): object {
        return (object) [$this->getName() => UploadedFile::class];
    }

}
