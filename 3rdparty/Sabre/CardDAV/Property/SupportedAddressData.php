<?php

/**
 * Supported-address-data property
 *
 * This property is a representation of the supported-address-data property
 * in the CardDAV namespace.
 *
 * @package Sabre
 * @subpackage CardDAV
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_CardDAV_Property_SupportedAddressData extends Sabre_DAV_Property {

    /**
     * supported versions
     *
     * @var array
     */
    protected $supportedData = array();

    /**
     * Creates the property
     *
     * @param array|null $supportedData
     */
    public function __construct(array $supportedData = null) {

        if (is_null($supportedData)) {
            $supportedData = array(
                array('contentType' => 'text/vcard', 'version' => '3.0'),
                array('contentType' => 'text/vcard', 'version' => '4.0'),
            );
        }

       $this->supportedData = $supportedData;

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

        $prefix =
            isset($server->xmlNamespaces[Sabre_CardDAV_Plugin::NS_CARDDAV]) ?
            $server->xmlNamespaces[Sabre_CardDAV_Plugin::NS_CARDDAV] :
            'card';

        foreach($this->supportedData as $supported) {

            $caldata = $doc->createElementNS(Sabre_CardDAV_Plugin::NS_CARDDAV, $prefix . ':address-data-type');
            $caldata->setAttribute('content-type',$supported['contentType']);
            $caldata->setAttribute('version',$supported['version']);
            $node->appendChild($caldata);

        }

    }

}
