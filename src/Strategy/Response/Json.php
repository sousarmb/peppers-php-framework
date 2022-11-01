<?php

namespace Peppers\Strategy\Response;

use Peppers\Contracts\PipelineStage;
use Peppers\Helpers\ResponseSent;
use Peppers\Helpers\Types\ResponseType;
use Peppers\Response;
use Peppers\Traits\HttpHeaders;

class Json implements PipelineStage {

    use HttpHeaders;

    /**
     * 
     * @param mixed $io
     * @return mixed
     */
    public function run(mixed $io): mixed {
        if (!($io instanceof Response) || (ResponseType::Json != $io->getType())) {
            return $io;
        }
        // start outputting headers and content
        http_response_code($io->getStatusCode() ?: 200);
        $json = json_encode($io->getResponse());
        // add download specific headers
        // if the developer has already set these there is no problem
        $io->hasHeader('content-type') ?:
                        $io->setHeader('content-type', 'application/json');
        $io->setHeader('content-length', strlen($json));
        // send headers to client browser
        foreach ($io->getHeaders() as $header => $value) {
            $this->sendHeader($header, $value);
        }
        ob_clean();
        echo $json;
        ob_end_flush();
        return new ResponseSent();
    }

}
