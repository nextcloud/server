<?php

/**
 * This is a simple plugin that will map any GET request for non-files to
 * PROPFIND allprops-requests.
 *
 * This should allow easy debugging of PROPFIND
 *
 * @package Sabre
 * @subpackage DAV
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_DAV_Browser_MapGetToPropFind extends Sabre_DAV_ServerPlugin {

    /**
     * reference to server class
     *
     * @var Sabre_DAV_Server
     */
    protected $server;

    /**
     * Initializes the plugin and subscribes to events
     *
     * @param Sabre_DAV_Server $server
     * @return void
     */
    public function initialize(Sabre_DAV_Server $server) {

        $this->server = $server;
        $this->server->subscribeEvent('beforeMethod',array($this,'httpGetInterceptor'));
    }

    /**
     * This method intercepts GET requests to non-files, and changes it into an HTTP PROPFIND request
     *
     * @param string $method
     * @param string $uri
     * @return bool
     */
    public function httpGetInterceptor($method, $uri) {

        if ($method!='GET') return true;

        $node = $this->server->tree->getNodeForPath($uri);
        if ($node instanceof Sabre_DAV_IFile) return;

        $this->server->invokeMethod('PROPFIND',$uri);
        return false;

    }

}
