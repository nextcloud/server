<?php

/**
 * This plugin provides support for RFC4709: Mounting WebDAV servers
 *
 * Simply append ?mount to any collection to generate the davmount response.
 *
 * @package Sabre
 * @subpackage DAV
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 */
class Sabre_DAV_Mount_Plugin extends Sabre_DAV_ServerPlugin {

    /**
     * Reference to Server class
     *
     * @var Sabre_DAV_Server
     */
    private $server;

    /**
     * Initializes the plugin and registers event handles
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
     * with ?mount
     *
     * @param string $method
     * @param string $uri
     * @return bool
     */
    public function beforeMethod($method, $uri) {

        if ($method!='GET') return;
        if ($this->server->httpRequest->getQueryString()!='mount') return;

        $currentUri = $this->server->httpRequest->getAbsoluteUri();

        // Stripping off everything after the ?
        list($currentUri) = explode('?',$currentUri);

        $this->davMount($currentUri);

        // Returning false to break the event chain
        return false;

    }

    /**
     * Generates the davmount response
     *
     * @param string $uri absolute uri
     * @return void
     */
    public function davMount($uri) {

        $this->server->httpResponse->sendStatus(200);
        $this->server->httpResponse->setHeader('Content-Type','application/davmount+xml');
        ob_start();
        echo '<?xml version="1.0"?>', "\n";
        echo "<dm:mount xmlns:dm=\"http://purl.org/NET/webdav/mount\">\n";
        echo "  <dm:url>", htmlspecialchars($uri, ENT_NOQUOTES, 'UTF-8'), "</dm:url>\n";
        echo "</dm:mount>";
        $this->server->httpResponse->sendBody(ob_get_clean());

    }


}
