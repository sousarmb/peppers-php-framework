<?php

namespace Peppers\Helpers;

use Peppers\Base\File;
use RuntimeException;
use Peppers\Helpers\Arrays;
use Settings;

class UploadedFile extends File {

    private string $_formName;
    private array $_metaData = [];
    private string $_path;
    private string $_tempName;

    public function __construct(
            string $formNameOrArrayNotation,
            string $mode = 'r'
    ) {
        // try to create/open existing file
        $metaData = Arrays::getFrom(
                        $_FILES,
                        $formNameOrArrayNotation
        );
        $this->handle = fopen(
                $metaData['tmp_name'],
                $mode
        );
        if (!is_resource($this->handle)) {
            throw new RuntimeException("Cannot open uploaded file $formNameOrArrayNotation");
        }

        $this->_metaData = $metaData;
        $this->_path = $metaData['tmp_name'];
        $this->_formName = $metaData['name'];
        $this->_tempName = basename($metaData['tmp_name']);
    }

    /**
     * 
     * @param string $filePath
     * @param bool $overwrite
     * @return type
     * @throws RuntimeException
     */
    public function moveTo(
            string $filePath = '',
            bool $overwrite = false
    ) {
        $privatePath = Settings::get('PRIVATE_DIR') . $filePath;
        if (is_dir($privatePath)) {
            throw new RuntimeException("$filePath is a directory");
        }
        if (!$overwrite && is_file($privatePath)) {
            throw new RuntimeException("Trying to overwrite existing file $filePath");
        }

        return move_uploaded_file($this->_path, $privatePath);
    }

    /**
     * 
     * @param bool $returnRealOrTemporary
     * @return string
     */
    public function getPath(bool $returnRealOrTemporary = false): string {
        return $returnRealOrTemporary ? $this->_path : $this->_tempName;
    }

    /**
     * Return name set in form
     * 
     * @return string
     */
    public function getName(): string {
        return $this->_formName;
    }

    /**
     * 
     * @return array
     */
    public function getMetaData(): array {
        return $this->_metaData;
    }

    /**
     * Uploaded files are deleted when request processing is over, always
     * temporary
     * 
     * @return bool
     */
    public function isTemporary(): bool {
        return true;
    }

    /**
     * $filePath should a string with exact form name parameter or array 
     * notation (form_array.index.name) where php placed the file location
     * 
     * @param string $filePath
     * @param string $mode
     * @return self
     * @throws RuntimeException
     */
    public static function open(string $filePath, string $mode = 'r'): self {
        $tmpPath = Arrays::getFrom(
                        $_FILES,
                        $filePath
                )['tmp_name'];
        if (self::exists($tmpPath)) {
            return new self(
                    $tmpPath,
                    $mode
            );
        }

        throw new RuntimeException("File $filePath not found in \$_FILES");
    }

    /**
     * Check if file is_readable() in system temporary directory 
     * 
     * @param string $filePath
     * @return bool
     */
    public static function exists(string $filePath): bool {
        return is_readable($filePath);
    }

    /**
     * Uploaded files are deleted when request processing is over
     * 
     * @return bool
     */
    public function unlink(): bool {
        return true;
    }

    /**
     * 
     * @return string
     */
    public function __toString(): string {
        return $this->_path;
    }

}
