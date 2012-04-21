<?php

/**
 * ResponseList property
 *
 * This class represents multiple {DAV:}response XML elements.
 * This is used by the Server class to encode items within a multistatus
 * response.
 *
 * @package Sabre
 * @subpackage DAV
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_DAV_Property_ResponseList extends Sabre_DAV_Property {

    /**
     * Response objects.
     *
     * @var array
     */
    private $responses;

    /**
     * The only valid argument is a list of Sabre_DAV_Property_Response
     * objects.
     *
     * @param array $responses;
     */
    public function __construct($responses) {

        foreach($responses as $response) {
            if (!($response instanceof Sabre_DAV_Property_Response)) {
                throw new InvalidArgumentException('You must pass an array of Sabre_DAV_Property_Response objects');
            }
        }
        $this->responses = $responses;

    }

    /**
     * serialize
     *
     * @param Sabre_DAV_Server $server
     * @param DOMElement $dom
     * @return void
     */
    public function serialize(Sabre_DAV_Server $server,DOMElement $dom) {

        foreach($this->responses as $response) {
            $response->serialize($server, $dom);
        }

    }

}
