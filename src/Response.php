<?php

namespace Peppers;

use Generator;
use Peppers\Contracts\IO;
use Peppers\Helpers\Http\Redirect;
use Peppers\Helpers\Types\ResponseType;
use Peppers\Renderer\HtmlView;
use SimpleXMLElement;

class Response {

    private ResponseType $_type;
    private mixed $_response;
    private int $_responseCode;
    private array $_responseHeaders = [];

    public function __construct() {
        $this->_type = ResponseType::NoBody;
    }

    /**
     * 
     * @return ResponseType
     */
    public function getType(): ResponseType {
        return $this->_type;
    }

    /**
     * 
     * @return mixed
     */
    public function getResponse(): mixed {
        return $this->_response;
    }

    /**
     * 
     * @param File $file
     * @return self
     */
    public function file(IO $file): self {
        $this->_response = $file;
        $this->_type = ResponseType::File;
        return $this;
    }

    /**
     * 
     * @param HtmlView $view
     * @return self
     */
    public function html(HtmlView $view): self {
        $this->_response = $view;
        $this->_type = ResponseType::Html;
        return $this;
    }

    /**
     * 
     * @param array|object $data
     * @return self
     */
    public function json(array|object $data): self {
        if ($data instanceof Generator) {
            $stuff = [];
            foreach ($data as $generator) {
                $stuff[] = $generator;
            }
        } else {
            $stuff = null;
        }

        $this->_response = $stuff ?? $data;
        $this->_type = ResponseType::Json;
        return $this;
    }

    /**
     * 
     * @param string|object $data
     * @return self
     */
    public function plainText(string|object $data): self {
        $this->_response = is_string($data) ? $data : (string) $data;
        $this->_type = ResponseType::PlainText;
        return $this;
    }

    /**
     * 
     * @param SimpleXMLElement $data
     * @return self
     */
    public function xml(SimpleXMLElement $data): self {
        $this->_response = $data;
        $this->_type = ResponseType::Xml;
        return $this;
    }

    /**
     * 
     * @return bool
     */
    public function hasHeaders(): bool {
        return [] != $this->_responseHeaders;
    }

    /**
     * 
     * @param string $header
     * @return bool
     */
    public function hasHeader(string $header): bool {
        return array_key_exists($header, $this->_responseHeaders);
    }

    /**
     * 
     * @param string $header
     * @return self
     */
    public function unsetHeader(string $header): self {
        if ($this->hasHeader($header)) {
            unset($this->_responseHeaders[$header]);
        }

        return $this;
    }

    /**
     * 
     * @param string $header
     * @param string $value
     * @return self
     */
    public function setHeader(string $header, string $value): self {
        $this->_responseHeaders[$header] = $value;
        return $this;
    }

    /**
     * 
     * @param int $code
     * @return self
     */
    public function setStatusCode(int $code): self {
        $this->_responseCode = $code;
        return $this;
    }

    /**
     * 
     * @return Generator
     */
    public function getHeaders(): Generator {
        foreach ($this->_responseHeaders as $header => $value) {
            yield $header => $value;
        }
    }

    /**
     * 
     * @return int|null
     */
    public function getStatusCode(): ?int {
        return isset($this->_responseCode) ? $this->_responseCode : null;
    }

    /**
     * 
     * @param Redirect $redirect
     * @return self
     */
    public function redirect(Redirect $redirect): self {
        $this->_response = $redirect;
        $this->_type = ResponseType::Redirect;
        return $this;
    }

}
