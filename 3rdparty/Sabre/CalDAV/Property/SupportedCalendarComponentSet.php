<?php

/**
 * Supported component set property
 *
 * This property is a representation of the supported-calendar_component-set
 * property in the CalDAV namespace. It simply requires an array of components,
 * such as VEVENT, VTODO
 *
 * @package Sabre
 * @subpackage CalDAV
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_CalDAV_Property_SupportedCalendarComponentSet extends Sabre_DAV_Property {

    /**
     * List of supported components, such as "VEVENT, VTODO"
     *
     * @var array
     */
    private $components;

    /**
     * Creates the property
     *
     * @param array $components
     */
    public function __construct(array $components) {

       $this->components = $components;

    }

    /**
     * Returns the list of supported components
     *
     * @return array
     */
    public function getValue() {

        return $this->components;

    }

    /**
     * Serializes the property in a DOMDocument
     *
     * @param Sabre_DAV_Server $server
     * @param DOMElement $node
     * @return void
     */
    public function serialize(Sabre_DAV_Server $server,DOMElement $node) {

       $doc = $node->ownerDocument;
       foreach($this->components as $component) {

            $xcomp = $doc->createElement('cal:comp');
            $xcomp->setAttribute('name',$component);
            $node->appendChild($xcomp);

       }

    }

    /**
     * Unserializes the DOMElement back into a Property class.
     *
     * @param DOMElement $node
     * @return Sabre_CalDAV_Property_SupportedCalendarComponentSet
     */
    static function unserialize(DOMElement $node) {

        $components = array();
        foreach($node->childNodes as $childNode) {
            if (Sabre_DAV_XMLUtil::toClarkNotation($childNode)==='{' . Sabre_CalDAV_Plugin::NS_CALDAV . '}comp') {
                $components[] = $childNode->getAttribute('name');
            }
        }
        return new self($components);

    }

}
