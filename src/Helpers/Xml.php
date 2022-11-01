<?php

namespace Peppers\Helpers;

use SimpleXMLElement;

abstract class Xml {

    private static string $typeDeclaration = '<?xml version="1.0" encoding="%s"?>';
    private static string $rootNode = '<%s/>';

    /**
     * 
     * @param string $rootNodeName
     * @param string|null $encoding If NULL get value from mb_internal_encoding()
     * @return SimpleXMLElement
     */
    public static function getSimpleEmptyDocument(
            string $rootNodeName = 'root',
            ?string $encoding = null
    ): SimpleXMLElement {
        $documentDeclaration = sprintf(
                static::$typeDeclaration . static::$rootNode,
                $encoding ?? mb_internal_encoding(),
                $rootNodeName
        );
        return new SimpleXMLElement(
                $documentDeclaration,
                LIBXML_NONET
        );
    }

}
