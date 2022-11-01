<?php

namespace Peppers\Helpers;

use Peppers\Base\File;
use Peppers\Helpers\Strings;
use RuntimeException;
use Settings;

class LocalFile extends File {

    private array $_metaData = [];
    private string $_name;
    private string $_path;
    protected bool $temporary = false;

    /**
     * Wrapper class for file related functions
     * 
     * @param string $filePath  If empty creates file in Settings::get('PRIVATE_DIR');
     *                          all files must be within Settings::get('PRIVATE_DIR')
     * @param string $mode      same modes as SAPI fopen()
     */
    public function __construct(
            string $filePath = '',
            string $mode = 'x+'
    ) {
        if (empty($filePath)) {
            // opening file with random name
            $this->handle = fopen(
                    Settings::get('PRIVATE_DIR') . Strings::getUniqueId(),
                    $mode
            );
        } else {
            // try to create/open existing file
            $this->handle = fopen(
                    self::addPrivateDirectoryPath($filePath),
                    $mode
            );
            if (!is_resource($this->handle)) {
                throw new RuntimeException("Cannot create/open file $filePath");
            }
        }

        $this->setMetaData();
        $this->_path = $this->_metaData['uri'];
        $this->_name = basename($this->_metaData['uri']);
    }

    /**
     * 
     * @return string
     */
    public function getPath(): string {
        return $this->_path;
    }

    /**
     * 
     * @return string
     */
    public function getName(): string {
        return $this->_name;
    }

    /**
     * 
     * @param string $filePath
     * @return string
     */
    private static function addPrivateDirectoryPath(string $filePath): string {
        if (0 !== strpos($filePath, Settings::get('PRIVATE_DIR'))) {
            $filePath = Settings::get('PRIVATE_DIR') . $filePath;
        }

        return $filePath;
    }

    /**
     * 
     * @return array
     */
    public function getMetaData(): array {
        return $this->_metaData;
    }

    /**
     * 
     * @return void
     */
    private function setMetaData(): void {
        $this->_metaData = stream_get_meta_data($this->handle);
    }

    /**
     * 
     * @return bool
     */
    public function isTemporary(): bool {
        return $this->temporary;
    }

    /**
     * Sets file as temporary. If possible file is deleted at the end of 
     * request processing 
     * 
     * @return self
     */
    public function setAsTemporary(): self {
        $this->temporary = true;
        return $this;
    }

    /**
     * 
     * @param string $filePath
     * @param string $mode
     * @return self
     * @throws RuntimeException
     */
    public static function open(string $filePath, string $mode = 'x+'): self {
        $privatePath = self::addPrivateDirectoryPath($filePath);
        if (self::exists($privatePath)) {
            return new self(
                    $privatePath,
                    $mode
            );
        }

        throw new RuntimeException("File $filePath does not exist");
    }

    /**
     * Check if file is_readable() in directory below Settings::get('PRIVATE_DIR')
     * 
     * @param string $filePath
     * @return bool
     */
    public static function exists(string $filePath): bool {
        return is_readable(
                self::addPrivateDirectoryPath($filePath)
        );
    }

    /**
     * 
     * @return bool
     */
    public function unlink(): bool {
        if (!$this->close()) {
            throw new RuntimeException('Could not close file before unlink');
        }

        return unlink($this->_path);
    }

    /**
     * 
     * @return string
     */
    public function __toString(): string {
        return $this->_path;
    }

}
