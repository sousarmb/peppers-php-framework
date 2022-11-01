<?php

namespace Peppers\Services;

use RuntimeException;
use Peppers\Helpers\Arrays;

class RequestBody {

    protected array $body;

    public function __construct() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_SERVER['CONTENT_LENGTH'] != '0') {
            switch ($_SERVER['CONTENT_TYPE']) {
                case 'application/x-www-form-urlencoded':
                case 0 === strpos(
                        $_SERVER['CONTENT_TYPE'],
                        'multipart/form-data;'
                ):
                    $this->body =& $_POST;
                    break;

                case 'application/json':
                    $input = fopen('php://input', 'r');
                    $this->body = json_decode(
                            stream_get_contents($input),
                            true,
                            512,
                            JSON_THROW_ON_ERROR
                    );
                    fclose($input);
                    break;
            }
        }
    }

    /**
     * 
     * @param string $dotNotationName   Search request body in a multi-level 
     *                                  array fashion
     * @return mixed    If NULL the $name was not found in the request body
     */
    public function find(string $dotNotationName): mixed {
        Arrays::getFrom($this->body, $dotNotationName);
    }

    /**
     * 
     * @param string $name
     * @return mixed    If NULL the $name was not found in the request body
     */
    public function __get(string $name): mixed {
        if (!isset($this->body) || !array_key_exists($name, $this->body)) {
            return null;
        }

        return $this->body[$name];
    }

    /**
     * 
     * @param string $name
     * @param mixed $value
     * @return void
     * @throws RuntimeException
     */
    public function __set(
            string $name,
            mixed $value
    ): void {
        throw new RuntimeException('Request body is read-only');
    }

}
