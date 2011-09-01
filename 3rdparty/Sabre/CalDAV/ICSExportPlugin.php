<?php

/**
 * ICS Exporter
 *
 * This plugin adds the ability to export entire calendars as .ics files.
 * This is useful for clients that don't support CalDAV yet. They often do 
 * support ics files.
 * 
 * @package Sabre
 * @subpackage CalDAV
 * @copyright Copyright (C) 2007-2011 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_CalDAV_ICSExportPlugin extends Sabre_DAV_ServerPlugin {

    /**
     * Reference to Server class 
     * 
     * @var Sabre_DAV_Server 
     */
    private $server;

    /**
     * Initializes the plugin and registers event handlers 
     * 
     * @param Sabre_DAV_Server $server 
     * @return void
     */
    public function initialize(Sabre_DAV_Server $server) {

        $this->server = $server;
        $this->server->subscribeEvent('beforeMethod',array($this,'beforeMethod'), 90);

    }

    /**
     * 'beforeMethod' event handles. This event handles intercepts GET requests ending
     * with ?export
     * 
     * @param string $method
     * @param string $uri
     * @return void
     */
    public function beforeMethod($method, $uri) {

        if ($method!='GET') return;
        if ($this->server->httpRequest->getQueryString()!='export') return;

        // splitting uri
        list($uri) = explode('?',$uri,2);

        $node = $this->server->tree->getNodeForPath($uri);

        if (!($node instanceof Sabre_CalDAV_Calendar)) return;

        $this->server->httpResponse->setHeader('Content-Type','text/calendar');
        $this->server->httpResponse->sendStatus(200);
        $this->server->httpResponse->sendBody($this->generateICS($this->server->tree->getChildren($uri)));

        // Returning false to break the event chain
        return false;

    }

    /**
     * Merges all calendar objects, and builds one big ics export 
     * 
     * @param array $nodes 
     * @return void
     */
    public function generateICS(array $nodes) {

        $calendar = new Sabre_VObject_Component('vcalendar');
        $calendar->version = '2.0';
        $calendar->prodid = '-//SabreDAV//SabreDAV ' . Sabre_DAV_Version::VERSION . '//EN';
        $calendar->calscale = 'GREGORIAN';

        $collectedTimezones = array();

        $timezones = array();
        $objects = array();

        foreach($nodes as $node) {

            $nodeData = $node->get();
            $nodeComp = Sabre_VObject_Reader::read($nodeData);

            foreach($nodeComp->children() as $child) {

                switch($child->name) {
                    case 'VEVENT' :
                    case 'VTODO' :
                    case 'VJOURNAL' :
                        $objects[] = $child;
                        break;

                    // VTIMEZONE is special, because we need to filter out the duplicates
                    case 'VTIMEZONE' :
                        // Naively just checking tzid.
                        if (in_array((string)$child->TZID, $collectedTimezones)) continue;

                        $timezones[] = $child;
                        $collectedTimezones[] = $child->TZID;
                        break;


                }


            }


        }

        foreach($timezones as $tz) $calendar->add($tz);
        foreach($objects as $obj) $calendar->add($obj);

        return $calendar->serialize();

    } 

}
