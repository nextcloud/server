<?php

/**
 * This property represents the {DAV:}getlastmodified property.
 *
 * Although this is normally a simple property, windows requires us to add
 * some new attributes.
 *
 * This class uses unix timestamps internally, and converts them to RFC 1123 times for
 * serialization
 *
 * @package Sabre
 * @subpackage DAV
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_DAV_Property_GetLastModified extends Sabre_DAV_Property {

    /**
     * time
     *
     * @var int
     */
    public $time;

    /**
     * __construct
     *
     * @param int|DateTime $time
     */
    public function __construct($time) {

        if ($time instanceof DateTime) {
            $this->time = $time;
        } elseif (is_int($time) || ctype_digit($time)) {
            $this->time = new DateTime('@' . $time);
        } else {
            $this->time = new DateTime($time);
        }

        // Setting timezone to UTC
        $this->time->setTimezone(new DateTimeZone('UTC'));

    }

    /**
     * serialize
     *
     * @param Sabre_DAV_Server $server
     * @param DOMElement       $prop
     * @return void
     */
    public function serialize(Sabre_DAV_Server $server, DOMElement $prop) {

        $doc = $prop->ownerDocument;
        $prop->setAttribute('xmlns:b','urn:uuid:c2f41010-65b3-11d1-a29f-00aa00c14882/');
        $prop->setAttribute('b:dt','dateTime.rfc1123');
        $prop->nodeValue = Sabre_HTTP_Util::toHTTPDate($this->time);

    }

    /**
     * getTime
     *
     * @return DateTime
     */
    public function getTime() {

        return $this->time;

    }

}

