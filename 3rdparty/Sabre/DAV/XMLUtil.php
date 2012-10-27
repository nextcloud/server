<?php

/**
 * XML utilities for WebDAV
 *
 * @package Sabre
 * @subpackage DAV
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_DAV_XMLUtil {

    /**
     * Returns the 'clark notation' for an element.
     *
     * For example, and element encoded as:
     * <b:myelem xmlns:b="http://www.example.org/" />
     * will be returned as:
     * {http://www.example.org}myelem
     *
     * This format is used throughout the SabreDAV sourcecode.
     *
     * This function will return null if a nodetype other than an Element is passed.
     *
     * @param DOMNode $dom
     * @return string
     */
    static function toClarkNotation(DOMNode $dom) {

        if ($dom->nodeType !== XML_ELEMENT_NODE) return null;

        $ns = $dom->namespaceURI;

        // Mapping to clark notation
        return '{' . $ns . '}' . $dom->localName;

    }

    /**
     * Parses a clark-notation string, and returns the namespace and element
     * name components.
     *
     * If the string was invalid, it will throw an InvalidArgumentException.
     *
     * @param string $str
     * @throws InvalidArgumentException
     * @return array
     */
    static function parseClarkNotation($str) {

        if (!preg_match('/^{([^}]*)}(.*)$/',$str,$matches)) {
            throw new InvalidArgumentException('\'' . $str . '\' is not a valid clark-notation formatted string');
        }

        return array(
            $matches[1],
            $matches[2]
        );

    }

    /**
     * This method provides a generic way to load a DOMDocument for WebDAV use.
     *
     * This method throws a Sabre_DAV_Exception_BadRequest exception for any xml errors.
     * It does not preserve whitespace.
     *
     * @param string $xml
     * @throws Sabre_DAV_Exception_BadRequest
     * @return DOMDocument
     */
    static function loadDOMDocument($xml) {

        if (empty($xml))
            throw new Sabre_DAV_Exception_BadRequest('Empty XML document sent');

        // The BitKinex client sends xml documents as UTF-16. PHP 5.3.1 (and presumably lower)
        // does not support this, so we must intercept this and convert to UTF-8.
        if (substr($xml,0,12) === "\x3c\x00\x3f\x00\x78\x00\x6d\x00\x6c\x00\x20\x00") {

            // Note: the preceeding byte sequence is "<?xml" encoded as UTF_16, without the BOM.
            $xml = iconv('UTF-16LE','UTF-8',$xml);

            // Because the xml header might specify the encoding, we must also change this.
            // This regex looks for the string encoding="UTF-16" and replaces it with
            // encoding="UTF-8".
            $xml = preg_replace('|<\?xml([^>]*)encoding="UTF-16"([^>]*)>|u','<?xml\1encoding="UTF-8"\2>',$xml);

        }

        // Retaining old error setting
        $oldErrorSetting =  libxml_use_internal_errors(true);

        // Clearing any previous errors
        libxml_clear_errors();

        $dom = new DOMDocument();

        // We don't generally care about any whitespace
        $dom->preserveWhiteSpace = false;
        
        $dom->loadXML($xml,LIBXML_NOWARNING | LIBXML_NOERROR);

        if ($error = libxml_get_last_error()) {
            libxml_clear_errors();
            throw new Sabre_DAV_Exception_BadRequest('The request body had an invalid XML body. (message: ' . $error->message . ', errorcode: ' . $error->code . ', line: ' . $error->line . ')');
        }

        // Restoring old mechanism for error handling
        if ($oldErrorSetting===false) libxml_use_internal_errors(false);

        return $dom;

    }

    /**
     * Parses all WebDAV properties out of a DOM Element
     *
     * Generally WebDAV properties are enclosed in {DAV:}prop elements. This
     * method helps by going through all these and pulling out the actual
     * propertynames, making them array keys and making the property values,
     * well.. the array values.
     *
     * If no value was given (self-closing element) null will be used as the
     * value. This is used in for example PROPFIND requests.
     *
     * Complex values are supported through the propertyMap argument. The
     * propertyMap should have the clark-notation properties as it's keys, and
     * classnames as values.
     *
     * When any of these properties are found, the unserialize() method will be
     * (statically) called. The result of this method is used as the value.
     *
     * @param DOMElement $parentNode
     * @param array $propertyMap
     * @return array
     */
    static function parseProperties(DOMElement $parentNode, array $propertyMap = array()) {

        $propList = array();
        foreach($parentNode->childNodes as $propNode) {

            if (Sabre_DAV_XMLUtil::toClarkNotation($propNode)!=='{DAV:}prop') continue;

            foreach($propNode->childNodes as $propNodeData) {

                /* If there are no elements in here, we actually get 1 text node, this special case is dedicated to netdrive */
                if ($propNodeData->nodeType != XML_ELEMENT_NODE) continue;

                $propertyName = Sabre_DAV_XMLUtil::toClarkNotation($propNodeData);
                if (isset($propertyMap[$propertyName])) {
                    $propList[$propertyName] = call_user_func(array($propertyMap[$propertyName],'unserialize'),$propNodeData);
                } else {
                    $propList[$propertyName] = $propNodeData->textContent;
                }
            }


        }
        return $propList;

    }

}
