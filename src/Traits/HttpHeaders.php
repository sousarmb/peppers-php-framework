<?php

namespace Peppers\Traits;

use Peppers\Exceptions\CannotRespondWithAcceptedContentType;
use Peppers\Helpers\Http\ContentNegotiation;
use Settings;

trait HttpHeaders {

    /**
     * 
     * @param string $header
     * @param string $value
     * @return void
     */
    public function sendHeader(
            string $header,
            string $value
    ): void {
        $lcaseHeader = strtolower($header);
        if ($this->headerAlreadySent($lcaseHeader)) {
            /* the developer has already output this header or it is a 
             * duplicate */
            return;
        } elseif ($lcaseHeader == 'content-type') {
            // we need to check if the client accepts this content type
            $this->sendContentTypeHeader($lcaseHeader, $value);
        } else {
            // set the header
            header("$lcaseHeader: $value");
        }
    }

    /**
     * 
     * @param string $header
     * @param string $mimeType
     * @return void
     * @throws CannotRespondWithAcceptedContentType
     */
    private function sendContentTypeHeader(
            string $header,
            string $mimeType
    ): void {
        if (strpos($mimeType, ';')) {
            // charset is included in the mime-type, do not use
            list($mimeType, $charset) = explode(';', $mimeType);
        } else {
            $charset = null;
        }

        $helper = new ContentNegotiation();
        if ($helper->clientAccepts($mimeType) || $helper->clientAccepts(Settings::get('HTTP_DEFAULT_MIME_TYPE'))) {
            $headerString = sprintf(
                    '%s: %s',
                    $header,
                    $mimeType . ($charset ? ";$charset" : '')
            );
            header($headerString);
        } else {
            throw new CannotRespondWithAcceptedContentType(
                            "$header: $mimeType",
                            $helper->getPreferences()
            );
        }
    }

    /**
     * 
     * @param string $header
     * @return bool
     */
    public function headerAlreadySent(string $header): bool {
        $headersList = isset($this->_headersList) ? null : headers_list();
        foreach ($headersList ?? $this->_headersList as $sentHeader) {
            if (0 === strpos(strtolower($sentHeader), $header)) {
                return true;
            }
        }
        return false;
    }

}
