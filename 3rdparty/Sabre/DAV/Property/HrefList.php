<?php

/**
 * HrefList property
 *
 * This property contains multiple {DAV:}href elements, each containing a url.
 *
 * @package Sabre
 * @subpackage DAV
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_DAV_Property_HrefList extends Sabre_DAV_Property {

    /**
     * hrefs
     *
     * @var array
     */
    private $hrefs;

    /**
     * Automatically prefix the url with the server base directory
     *
     * @var bool
     */
    private $autoPrefix = true;

    /**
     * __construct
     *
     * @param array $hrefs
     * @param bool $autoPrefix
     */
    public function __construct(array $hrefs, $autoPrefix = true) {

        $this->hrefs = $hrefs;
        $this->autoPrefix = $autoPrefix;

    }

    /**
     * Returns the uris
     *
     * @return array
     */
    public function getHrefs() {

        return $this->hrefs;

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
    public function serialize(Sabre_DAV_Server $server,DOMElement $dom) {

        $prefix = $server->xmlNamespaces['DAV:'];

        foreach($this->hrefs as $href) {
            $elem = $dom->ownerDocument->createElement($prefix . ':href');
            $elem->nodeValue = ($this->autoPrefix?$server->getBaseUri():'') . $href;
            $dom->appendChild($elem);
        }

    }

    /**
     * Unserializes this property from a DOM Element
     *
     * This method returns an instance of this class.
     * It will only decode {DAV:}href values.
     *
     * @param DOMElement $dom
     * @return Sabre_DAV_Property_HrefList
     */
    static function unserialize(DOMElement $dom) {

        $hrefs = array();
        foreach($dom->childNodes as $child) {
            if (Sabre_DAV_XMLUtil::toClarkNotation($child)==='{DAV:}href') {
                $hrefs[] = $child->textContent;
            }
        }
        return new self($hrefs, false);

    }

}
