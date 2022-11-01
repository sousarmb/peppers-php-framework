<?php

namespace Peppers\Strategy\Response;

use Peppers\Contracts\PipelineStage;
use Peppers\Helpers\Http\ContentNegotiation;
use Peppers\Helpers\ResponseSent;
use Peppers\Helpers\Types\ResponseType;
use Peppers\Response;
use Peppers\Traits\HttpHeaders;

class Html implements PipelineStage {

    use HttpHeaders;

    /**
     *
     * @param mixed $io
     * @return mixed
     */
    public function run(mixed $io): mixed {
        if (!($io instanceof Response) || (ResponseType::Html != $io->getType())) {
            return $io;
        }
        // start outputting headers and content
        $view = $io->getResponse();
        /* create temporary file in system temp directory to avoid name 
         * collisions */
        $tempFile = tmpfile();
        fwrite($tempFile, $view->render());

        // start outputting headers and content
        http_response_code($io->getStatusCode() ?: 200);
        $filePath = stream_get_meta_data($tempFile)['uri'];
        ob_clean();
        // get view variables so they may be accessed in the view
        $viewVariables = $view->getViewVariables() ?: (object) [];
        // include the HTML to be sent to browser/user agent
        include_once $filePath;
        $html = ob_get_contents();
        ob_clean();
        /* remove these headers because we only know the real values after 
         * include and to prevent "guessing" by PHP */
        header_remove('content-type');
        header_remove('content-length');
        // send headers to client browser
        $io->setHeader('content-type',
                ContentNegotiation::guessFileMimeType($filePath)
        );
        $io->setHeader('content-length', strlen($html));
        // send headers to client browser
        foreach ($io->getHeaders() as $header => $value) {
            $this->sendHeader($header, $value);
        }
        echo $html;
        ob_end_flush();
        // delete temporary file
        fclose($tempFile);
        // make sure no other response is sent
        return new ResponseSent();
    }

}
