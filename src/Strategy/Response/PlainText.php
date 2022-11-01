<?php

namespace Peppers\Strategy\Response;

use Peppers\Contracts\PipelineStage;
use Peppers\Helpers\ResponseSent;
use Peppers\Helpers\Types\ResponseType;
use Peppers\Response;
use Peppers\Traits\HttpHeaders;

class PlainText implements PipelineStage {

    use HttpHeaders;

    /**
     *
     * @param mixed $io
     * @return mixed
     */
    public function run(mixed $io): mixed {
        if (!($io instanceof Response) || (ResponseType::PlainText != $io->getType())) {
            return $io;
        }
        // start outputting headers and content
        http_response_code($io->getStatusCode() ?: 200);
        $text = trim($io->getResponse());
        // add download specific headers
        // if the developer has already set these there is no problem
        $io->hasHeader('content-type') ?:
                        $io->setHeader('content-type', 'text/plain');
        $io->setHeader('content-length', strlen($text));
        // send headers to client browser
        foreach ($io->getHeaders() as $header => $value) {
            $this->sendHeader($header, $value);
        }
        ob_clean();
        echo $text;
        ob_end_flush();
        return new ResponseSent();
    }

}
