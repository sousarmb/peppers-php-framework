<?php

namespace Peppers\Strategy;

use Peppers\Contracts\DefaultMethod;
use Peppers\Contracts\PipelineStage;
use Peppers\Exceptions\CannotRespondWithAcceptedContentType;
use Peppers\Helpers\Http\ContentNegotiation;
use Peppers\Helpers\Http\StatusCode;
use Peppers\Helpers\ResponseSent;
use Peppers\Helpers\ViewDataStore;
use Peppers\Helpers\Xml;
use Peppers\Kernel;
use Peppers\Renderer\HtmlView;
use Peppers\Response;
use Settings;
use SimpleXMLElement;
use Throwable;

class ResolveException implements PipelineStage {

    private ContentNegotiation $_helper;

    /**
     * 
     * @param ContentNegotiation $helper
     */
    public function __construct(ContentNegotiation $helper) {
        $this->_helper = $helper;
    }

    /**
     * 
     * @param mixed $io
     * @return mixed
     */
    public function run(mixed $io): mixed {
        if ($io instanceof DefaultMethod) {
            $io->default();
        }
        if ($io instanceof CannotRespondWithAcceptedContentType) {
            // prevent exception loop, fail quietly
            Kernel::log($io->getMessage(), $io->getTrace());
            http_response_code($io->getCode());
            return new ResponseSent();
        }
        // we can handle this :)
        $response = new Response();
        $statusCode = $io->getCode();
        $response->setStatusCode(
                StatusCode::has($statusCode) ? $statusCode : 500
        );
        // transform exception for next pipeline stages
        if ($this->_helper->clientAccepts('text/html')) {
            $response->html($this->sendHtml($io));
        } elseif ($this->_helper->clientAccepts('application/json')) {
            $response->json($this->sendJson($io));
        } elseif ($this->_helper->clientAccepts('application/xml') || $this->_helper->clientAccepts('text/xml')) {
            $response->xml($this->sendXml($io));
        } elseif ($this->_helper->clientAccepts('text/plain') || $this->_helper->clientAccepts('*/*')) {
            $response->plainText($this->sendPlainText($io));
        }

        return $response;
    }

    /**
     * 
     * @param Throwable $t
     * @return string
     */
    private function sendPlainText(Throwable $t): string {
        if (Settings::appInProduction()) {
            return sprintf('Peppers Exception | Message: %s',
                    $t->getMessage()
            );
        }

        return sprintf('Peppers Exception | Message: %s | Code: %s | File: %s | Line: %s | Trace: %s',
                $t->getMessage(),
                $t->getCode(),
                $t->getFile(),
                $t->getLine(),
                $t->getTraceAsString()
        );
    }

    /**
     * 
     * @param Throwable $t
     * @return SimpleXMLElement
     */
    private function sendXml(Throwable $t): SimpleXMLElement {
        $document = Xml::getSimpleEmptyDocument('Exception');
        if (Settings::appInProduction()) {
            $document->addChild('message', $t->getMessage());
            return $document;
        }

        $document->addChild('code', $t->getCode());
        $document->addChild('file', $t->getFile());
        $document->addChild('line', $t->getLine());
        $trace = $document->addChild('trace');
        foreach ($t->getTrace() as $k => $v) {
            $trace->addChild($k, $v);
        }

        return $document;
    }

    /**
     * 
     * @param Throwable $t
     * @return array
     */
    private function sendJson(Throwable $t): array {
        $data = ['message' => $t->getMessage()];
        if (Settings::appInProduction()) {
            return $data;
        }

        $data['code'] = $t->getCode();
        $data['file'] = $t->getFile();
        $data['line'] = $t->getLine();
        $data['trace'] = $t->getTrace();
        return $data;
    }

    /**
     * 
     * @param Throwable $t
     * @return HtmlView
     */
    private function sendHtml(Throwable $t): HtmlView {
        $viewData = new ViewDataStore(['message' => $t->getMessage()]);
        if (!Settings::appInProduction()) {
            $viewData->code = $t->getCode();
            $viewData->file = $t->getFile();
            $viewData->line = $t->getLine();
            $viewData->trace = $t->getTrace();
        }

        return new HtmlView(
                Settings::get('UNCAUGHT_EXCEPTION_DEFAULT_VIEW'),
                $viewData
        );
    }

}
