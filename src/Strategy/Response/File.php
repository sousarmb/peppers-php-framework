<?php

namespace Peppers\Strategy\Response;

use Peppers\Contracts\PipelineStage;
use Peppers\Helpers\Http\ContentNegotiation;
use Peppers\Helpers\ResponseSent;
use Peppers\Helpers\Types\ResponseType;
use Peppers\Response;
use Peppers\Traits\HttpHeaders;

class File implements PipelineStage {

    use HttpHeaders;

    /**
     *
     * @param mixed $io
     * @return mixed
     */
    public function run(mixed $io): mixed {
        if (!($io instanceof Response) || (ResponseType::File != $io->getType())) {
            return $io;
        }
        // start outputting headers and content
        $file = $io->getResponse();
        // prevent problems on file
        $file->close();
        // start outputting headers and content
        http_response_code($io->getStatusCode() ?: 200);
        // add download specific headers
        // if the developer has already set these there is no problem
        $io->hasHeader('content-type') ?:
                        $io->setHeader('content-type',
                                ContentNegotiation::guessFileMimeType($file->getPath())
        );
        $io->hasHeader('content-length') ?:
                        $io->setHeader('content-length',
                                filesize($file->getPath())
        );
        $io->hasHeader('content-disposition') ?:
                        $io->setHeader('content-disposition',
                                sprintf('attachment; filename="%s"', $file->getName())
        );
        // send headers to client browser
        foreach ($io->getHeaders() as $header => $value) {
            $this->sendHeader($header, $value);
        }
        ob_clean();
        readfile($file->getPath());
        ob_end_flush();
        // leave no trace behind...
        if ($file->isTemporary()) {
            $file->unlink();
        }
        // make sure no other response is sent
        return new ResponseSent();
    }

}
