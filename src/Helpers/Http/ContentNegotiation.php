<?php

namespace Peppers\Helpers\Http;

use RuntimeException;
use Settings;

class ContentNegotiation {

    private array $_preferences = [];

    /**
     *
     * @param bool $getFromCurrent
     */
    public function __construct(
            bool $getFromCurrent = true,
            array $mimeTypes = []
    ) {
        if ($getFromCurrent) {
            $this->getFromCurrent();
        } else {
            $this->_preferences = array_merge($this->_preferences, $mimeTypes);
        }
    }

    private function getFromCurrent(): void {
        if (!array_key_exists('HTTP_ACCEPT', $_SERVER) || $_SERVER['HTTP_ACCEPT'] == '*/*') {
            $this->_preferences[] = '*/*';
            return;
        }

        preg_match_all(
                Settings::get('REGEX_DEFAULT_HTTP_ACCEPT_HEADER'),
                $_SERVER['HTTP_ACCEPT'],
                $matches
        );
        for ($current = 0, $end = count($matches[0]); $current < $end; $current++) {
            $types = explode(
                    ',',
                    substr($matches[0][$current], 0,
                            strpos($matches[0][$current], ';') ?: null
                    )
            );
            $this->_preferences = array_merge($this->_preferences, $types);
        }
    }

    /**
     *
     * @return array
     */
    public function getPreferences(): array {
        return $this->_preferences;
    }

    /**
     *
     * @param string $mimetype
     * @return bool
     */
    public function clientAccepts(string $mimetype): bool {
        return in_array($mimetype, $this->_preferences);
    }

    /**
     *
     * @param string $prefers
     * @param string $that
     * @return bool
     * @throws RuntimeException
     */
    public function clientPrefersThisOverThat(
            string $prefers,
            string $that
    ): bool {
        $existsPreference = array_search($prefers, $this->_preferences);
        $existsThat = array_search($that, $this->_preferences);
        if ($existsPreference === false && $existsThat === false) {
            throw new RuntimeException('Client accepts neither MIME types!');
        } elseif ($existsPreference === true && $existsThat === false) {
            // client prefers $prefers mime
            return true;
        } elseif ($existsPreference === false && $existsThat === true) {
            // app prefers $prefers but client "only" accepts $that mime
            return false;
        } else {
            // which is first in the list of preferences?
            return $existsPreference <= $existsThat;
        }
    }

    /**
     * 
     * @param string $filePath
     * @return string
     * @throws RuntimeException
     */
    public static function guessFileMimeType(string $filePath): string {
        $finfo = finfo_open(FILEINFO_MIME);
        $mimeType = finfo_file($finfo, $filePath);
        finfo_close($finfo);
        if (false === $mimeType) {
            throw new RuntimeException('Unable to assert MIME type for file');
        }

        return $mimeType;
    }

}
