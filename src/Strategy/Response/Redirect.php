<?php

namespace Peppers\Strategy\Response;

use Peppers\Contracts\PipelineStage;
use Peppers\Helpers\ResponseSent;
use Peppers\Helpers\Types\ResponseType;
use Peppers\Response;
use Peppers\Traits\HttpHeaders;

class Redirect implements PipelineStage {

    use HttpHeaders;

    /**
     *
     * @param mixed $io
     * @return mixed
     */
    public function run(mixed $io): mixed {
        if (!($io instanceof Response) || (ResponseType::Redirect != $io->getType())) {
            return $io;
        }
        // start outputting headers and content
        $redirect = $io->getResponse();
        http_response_code($io->getStatusCode() ?: 200);
        // add download specific headers
        // if the developer has already set these there is no problem
        $io->setHeader('location', $redirect->resolve());
        // send headers to client browser
        foreach ($io->getHeaders() as $header => $value) {
            $this->sendHeader($header, $value);
        }
        // make sure the client does not get any context back
        ob_clean();
        // make sure no other response is sent
        return new ResponseSent();
    }

}
