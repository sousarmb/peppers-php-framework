<?php

namespace Peppers\Renderer;

use RuntimeException;
use Peppers\Contracts\View;
use Peppers\Helpers\ViewDataStore;
use Settings;

class HtmlView implements View {

    private array $_extends = [];
    private array $_files = [];
    private string $_html;
    private static string $_regexExtend = '/@extends\("([a-z0-9\-\.]+)"\)/i';
    private static string $_regexSection = '/@section\("([a-z0-9\-]+\.?)+"\)(\W|\w)+?@endsection/i';
    private static string $_regexUse = '/@use\("([a-z0-9\-\.]+)"\)+/i';
    private static string $_regexYield = '/@yield\("([a-z0-9\-]+\.?)+"\)/i';
    private bool $_rendered = false;
    private array $_sections = [];
    private string $_startSection;
    private object $_viewData;
    private string $_viewPath;

    /**
     * 
     * @param string $name
     * @param ViewDataStore|null $data
     * @throws RuntimeException
     */
    public function __construct(
            string $name,
            ?ViewDataStore $data = null
    ) {
        // assert if the view file exists and is readable
        $viewPath = explode('.', $name);
        if (count($viewPath) == 1) {
            // file inside views root directory
            $viewPath = $startSection = $name;
        } else {
            /* file inside views subdirectory, remove the last array value
             * it's the name of the start section */
            $startSection = array_pop($viewPath);
            $viewPath = implode(DIRECTORY_SEPARATOR, $viewPath);
        }

        $viewPath = Settings::get('APP_VIEW_DIR') . $viewPath . '.phtml';
        if (!is_readable($viewPath)) {
            throw new RuntimeException("Could not find/open initial view $name");
        }

        $this->_startSection = $startSection;
        $this->_viewPath = $viewPath;
        $this->_viewData = is_null($data) ? new ViewDataStore() : $data->protect();
    }

    /**
     * 
     * @return ViewDataStore
     */
    public function getViewVariables(): ViewDataStore {
        return $this->_viewData;
    }

    /**
     * 
     * @return mixed
     */
    public function render(): mixed {
        if ($this->_rendered) {
            return $this->_html;
        }
        /* prepare all the necessary view components/files 
         * scan all the envolved files for @extend, @use, @section clauses
         * starting with the bottom @section */
        $this->findExtensions($this->_viewPath);
        // build the actual HTML content
        $this->buildViewFromPieces();
        return $this->_html;
    }

    /**
     * 
     * @return void
     * @throws RuntimeException
     */
    private function buildViewFromPieces(): void {
        if ($this->_extends) {
            /* the section that gets rendered is the one with the same name as
             * the file; this has been asserted when looking for @extends */
            $temp = explode('.', end($this->_extends)[1]);
            $startSection = end($temp);
            unset($temp);
        } else {
            // use the section the developer defined
            $startSection = $this->_startSection;
        }
        /* go to the top level section and work to the bottom, replacing
         * @yields within the start section */
        if (!array_key_exists($startSection, $this->_sections)) {
            throw new RuntimeException("Section $startSection not found");
        }

        $html = $this->_sections[$startSection];
        do {
            preg_match_all(self::$_regexYield, $html, $matches);
            /* get the @sections content, using the matches[1] array - which
             * holds the key/string section name ("exampleTitle") and then 
             * using it to get the replacement values from/for the 
             * $this->_sections  ('@yield("exampleTitle")') */
            $matches[1] = array_map(fn($key) => $this->_sections[$key], $matches[1]);
            // replace the @yields with actual HTML
            $html = str_replace($matches[0], $matches[1], $html);
            // ... and keep doing it while there are @yields to replace
        } while ($matches[0]);
        $this->_html = $html;
        $this->_rendered = !$this->_rendered;
    }

    /**
     * 
     * @param string $file
     * @return void
     * @throws RuntimeException
     */
    private function findExtensions(string $file): void {
        $fileContents = file_get_contents($file);
        if (!is_string($fileContents)) {
            throw new RuntimeException('Could not find/open view file');
        }
        $this->_files[] = $file;
        // find @extends
        preg_match(self::$_regexExtend, $fileContents, $matches);
        if ($matches) {
            // get the 1st one (one view can only extend one template)
            $this->_extends[] = $matches;
            // find the next file
            $this->findExtensions($this->getFilePath($matches[1]));
        }
        // find @use if any
        $this->findUses($fileContents);
    }

    /**
     * 
     * @param string $fileContent
     * @return void
     */
    private function findUses(string $fileContent): void {
        // look for @use clauses
        preg_match_all(self::$_regexUse, $fileContent, $matches);
        if ($matches) {
            foreach ($matches[1] as $useFile) {
                // more @uses, get those as well
                $filePath = $this->getFilePath($useFile);
                if (in_array($filePath, $this->_files)) {
                    // file already processed
                    continue;
                }
                // record that the file was loaded
                $this->_files[] = $filePath;
                // find more @uses
                $this->findUses(file_get_contents($filePath));
            }
        }
        // find @sections if any
        $this->findSections($fileContent);
    }

    /**
     * 
     * @param string $fileContent
     * @return void
     * @throws RuntimeException
     */
    private function findSections(string $fileContent): void {
        preg_match_all(self::$_regexSection, $fileContent, $matches);
        if (!$matches) {
            return;
        }

        foreach ($matches[0] as $key => $section) {
            $sectionName = $matches[1][$key];
            if (array_key_exists($sectionName, $this->_sections)) {
                throw new RuntimeException("Duplicate View section name $sectionName found");
            }

            $this->_sections[$sectionName] = $this->getSectionContent($section);
        }
    }

    /**
     *
     * @param string $section
     * @return string
     */
    private function getSectionContent(string $section): string {
        $sectionStart = substr(
                $section,
                strpos($section, PHP_EOL)
        );
        $sectionEnd = strrpos($sectionStart, PHP_EOL);
        return trim(
                substr(
                        $sectionStart,
                        0,
                        $sectionEnd,
                )
        );
    }

    /**
     * 
     * @param string $name
     * @return string
     */
    private function getFilePath(string $name): string {
        return Settings::get('APP_VIEW_DIR') . str_replace('.', DIRECTORY_SEPARATOR, $name) . '.phtml';
    }

}
