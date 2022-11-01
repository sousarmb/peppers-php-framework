<?php

namespace Peppers\Strategy\Response;

use Peppers\Contracts\PipelineStage;
use Peppers\Helpers\ResponseSent;
use Peppers\Helpers\Types\ResponseType;
use Peppers\Response;
use Peppers\Traits\HttpHeaders;

class Xml implements PipelineStage {

    use HttpHeaders;

    /**
     * 
     * @param mixed $io
     * @return mixed
     */
    public function run(mixed $io): mixed {
        if (!($io instanceof Response) || (ResponseType::Xml != $io->getType())) {
            return $io;
        }
        // start outputting headers and content
        $xmlString = $io->getResponse()->asXML();
        http_response_code($io->getStatusCode() ?: 200);
        // add download specific headers
        // if the developer has already set these there is no problem
        $io->hasHeader('content-type') ?:
                        $io->setHeader('content-type', 'application/xml');
        $io->setHeader('content-length', strlen($xmlString));
        // send headers to client browser
        foreach ($io->getHeaders() as $header => $value) {
            $this->sendHeader($header, $value);
        }
        ob_clean();
        echo $xmlString;
        ob_end_flush();
        return new ResponseSent();
    }

}
