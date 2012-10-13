<?php

/**
 * Principal property
 *
 * The principal property represents a principal from RFC3744 (ACL).
 * The property can be used to specify a principal or pseudo principals.
 *
 * @package Sabre
 * @subpackage DAVACL
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_DAVACL_Property_Principal extends Sabre_DAV_Property implements Sabre_DAV_Property_IHref {

    /**
     * To specify a not-logged-in user, use the UNAUTHENTICTED principal
     */
    const UNAUTHENTICATED = 1;

    /**
     * To specify any principal that is logged in, use AUTHENTICATED
     */
    const AUTHENTICATED = 2;

    /**
     * Specific principals can be specified with the HREF
     */
    const HREF = 3;

    /**
     * Everybody, basically
     */
    const ALL = 4;

    /**
     * Principal-type
     *
     * Must be one of the UNAUTHENTICATED, AUTHENTICATED or HREF constants.
     *
     * @var int
     */
    private $type;

    /**
     * Url to principal
     *
     * This value is only used for the HREF principal type.
     *
     * @var string
     */
    private $href;

    /**
     * Creates the property.
     *
     * The 'type' argument must be one of the type constants defined in this class.
     *
     * 'href' is only required for the HREF type.
     *
     * @param int $type
     * @param string|null $href
     */
    public function __construct($type, $href = null) {

        $this->type = $type;

        if ($type===self::HREF && is_null($href)) {
            throw new Sabre_DAV_Exception('The href argument must be specified for the HREF principal type.');
        }
        $this->href = $href;

    }

    /**
     * Returns the principal type
     *
     * @return int
     */
    public function getType() {

        return $this->type;

    }

    /**
     * Returns the principal uri.
     *
     * @return string
     */
    public function getHref() {

        return $this->href;

    }

    /**
     * Serializes the property into a DOMElement.
     *
     * @param Sabre_DAV_Server $server
     * @param DOMElement $node
     * @return void
     */
    public function serialize(Sabre_DAV_Server $server, DOMElement $node) {

        $prefix = $server->xmlNamespaces['DAV:'];
        switch($this->type) {

            case self::UNAUTHENTICATED :
                $node->appendChild(
                    $node->ownerDocument->createElement($prefix . ':unauthenticated')
                );
                break;
            case self::AUTHENTICATED :
                $node->appendChild(
                    $node->ownerDocument->createElement($prefix . ':authenticated')
                );
                break;
            case self::HREF :
                $href = $node->ownerDocument->createElement($prefix . ':href');
                $href->nodeValue = $server->getBaseUri() . $this->href;
                $node->appendChild($href);
                break;

        }

    }

    /**
     * Deserializes a DOM element into a property object.
     *
     * @param DOMElement $dom
     * @return Sabre_DAV_Property_Principal
     */
    static public function unserialize(DOMElement $dom) {

        $parent = $dom->firstChild;
        while(!Sabre_DAV_XMLUtil::toClarkNotation($parent)) {
            $parent = $parent->nextSibling;
        }

        switch(Sabre_DAV_XMLUtil::toClarkNotation($parent)) {

            case '{DAV:}unauthenticated' :
                return new self(self::UNAUTHENTICATED);
            case '{DAV:}authenticated' :
                return new self(self::AUTHENTICATED);
            case '{DAV:}href':
                return new self(self::HREF, $parent->textContent);
            case '{DAV:}all':
                return new self(self::ALL);
            default :
                throw new Sabre_DAV_Exception_BadRequest('Unexpected element (' . Sabre_DAV_XMLUtil::toClarkNotation($parent) . '). Could not deserialize');

        }

    }

}
