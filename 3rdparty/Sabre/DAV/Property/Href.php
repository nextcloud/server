<?php

/**
 * Href property
 *
 * The href property represents a url within a {DAV:}href element.
 * This is used by many WebDAV extensions, but not really within the WebDAV core spec
 *
 * @package Sabre
 * @subpackage DAV
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_DAV_Property_Href extends Sabre_DAV_Property implements Sabre_DAV_Property_IHref {

    /**
     * href
     *
     * @var string
     */
    private $href;

    /**
     * Automatically prefix the url with the server base directory
     *
     * @var bool
     */
    private $autoPrefix = true;

    /**
     * __construct
     *
     * @param string $href
     * @param bool $autoPrefix
     */
    public function __construct($href, $autoPrefix = true) {

        $this->href = $href;
        $this->autoPrefix = $autoPrefix;

    }

    /**
     * Returns the uri
     *
     * @return string
     */
    public function getHref() {

        return $this->href;

    }

    /**
     * Serializes this property.
     *
     * It will additionally prepend the href property with the server's base uri.
     *
     * @param Sabre_DAV_Server $server
     * @param DOMElement $dom
     * @return void
     */
    public function serialize(Sabre_DAV_Server $server, DOMElement $dom) {

        $prefix = $server->xmlNamespaces['DAV:'];

        $elem = $dom->ownerDocument->createElement($prefix . ':href');
        $elem->nodeValue = ($this->autoPrefix?$server->getBaseUri():'') . $this->href;
        $dom->appendChild($elem);

    }

    /**
     * Unserializes this property from a DOM Element
     *
     * This method returns an instance of this class.
     * It will only decode {DAV:}href values. For non-compatible elements null will be returned.
     *
     * @param DOMElement $dom
     * @return Sabre_DAV_Property_Href
     */
    static function unserialize(DOMElement $dom) {

        if ($dom->firstChild && Sabre_DAV_XMLUtil::toClarkNotation($dom->firstChild)==='{DAV:}href') {
            return new self($dom->firstChild->textContent,false);
        }

    }

}
