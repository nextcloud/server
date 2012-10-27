<?php

/**
 * schedule-calendar-transp property.
 *
 * This property is a representation of the schedule-calendar-transp property.
 * This property is defined in RFC6638 (caldav scheduling).
 *
 * Its values are either 'transparent' or 'opaque'. If it's transparent, it
 * means that this calendar will not be taken into consideration when a
 * different user queries for free-busy information. If it's 'opaque', it will.
 *
 * @package Sabre
 * @subpackage CalDAV
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_CalDAV_Property_ScheduleCalendarTransp extends Sabre_DAV_Property {

    const TRANSPARENT = 'transparent';
    const OPAQUE = 'opaque';

    protected $value;

    /**
     * Creates the property
     *
     * @param string $value
     */
    public function __construct($value) {

        if ($value !== self::TRANSPARENT && $value !== self::OPAQUE) {
            throw new \InvalidArgumentException('The value must either be specified as "transparent" or "opaque"');
        }
        $this->value = $value;

    }

    /**
     * Returns the current value
     *
     * @return string
     */
    public function getValue() {

        return $this->value;

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
        switch($this->value) {
            case self::TRANSPARENT :
                $xval = $doc->createElement('cal:transparent');
                break;
            case self::OPAQUE :
                $xval = $doc->createElement('cal:opaque');
                break;
        }

        $node->appendChild($xval);

    }

    /**
     * Unserializes the DOMElement back into a Property class.
     *
     * @param DOMElement $node
     * @return Sabre_CalDAV_Property_ScheduleCalendarTransp
     */
    static function unserialize(DOMElement $node) {

        $value = null;
        foreach($node->childNodes as $childNode) {
            switch(Sabre_DAV_XMLUtil::toClarkNotation($childNode)) {
                case '{' . Sabre_CalDAV_Plugin::NS_CALDAV . '}opaque' :
                    $value = self::OPAQUE;
                    break;
                case '{' . Sabre_CalDAV_Plugin::NS_CALDAV . '}transparent' :
                    $value = self::TRANSPARENT;
                    break;
            }
        }
        if (is_null($value))
           return null;

        return new self($value);

    }
}
