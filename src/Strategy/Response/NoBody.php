<?php

namespace Peppers\Strategy\Response;

use Peppers\Contracts\PipelineStage;
use Peppers\Helpers\ResponseSent;
use Peppers\Helpers\Types\ResponseType;
use Peppers\Response;
use Peppers\Traits\HttpHeaders;

class NoBody implements PipelineStage {

    use HttpHeaders;

    /**
     *
     * @param mixed $io
     * @return mixed
     */
    public function run(mixed $io): mixed {
        if (!($io instanceof Response) || (ResponseType::NoBody != $io->getType())) {
            return $io;
        }
        // start outputting headers and content
        http_response_code($io->getStatusCode() ?: 204);
        // add download specific headers
        // if the developer has already set these there is no problem
        $io->setHeader('content-length', '0');
        foreach ($io->getHeaders() as $header => $value) {
            $this->sendHeader($header, $value);
        }
        // make sure the client does not get any context back
        ob_clean();
        // make sure no other response is sent
        return new ResponseSent();
    }

}
