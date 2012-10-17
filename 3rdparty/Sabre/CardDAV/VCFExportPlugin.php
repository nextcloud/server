<?php

use Sabre\VObject;

/**
 * VCF Exporter
 *
 * This plugin adds the ability to export entire address books as .vcf files.
 * This is useful for clients that don't support CardDAV yet. They often do
 * support vcf files.
 *
 * @package Sabre
 * @subpackage CardDAV
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @author Thomas Tanghus (http://tanghus.net/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_CardDAV_VCFExportPlugin extends Sabre_DAV_ServerPlugin {

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
     * @return bool
     */
    public function beforeMethod($method, $uri) {

        if ($method!='GET') return;
        if ($this->server->httpRequest->getQueryString()!='export') return;

        // splitting uri
        list($uri) = explode('?',$uri,2);

        $node = $this->server->tree->getNodeForPath($uri);

        if (!($node instanceof Sabre_CardDAV_IAddressBook)) return;

        // Checking ACL, if available.
        if ($aclPlugin = $this->server->getPlugin('acl')) {
            $aclPlugin->checkPrivileges($uri, '{DAV:}read');
        }

        $this->server->httpResponse->setHeader('Content-Type','text/directory');
        $this->server->httpResponse->sendStatus(200);

        $nodes = $this->server->getPropertiesForPath($uri, array(
            '{' . Sabre_CardDAV_Plugin::NS_CARDDAV . '}address-data',
        ),1);

        $this->server->httpResponse->sendBody($this->generateVCF($nodes));

        // Returning false to break the event chain
        return false;

    }

    /**
     * Merges all vcard objects, and builds one big vcf export
     *
     * @param array $nodes
     * @return string
     */
    public function generateVCF(array $nodes) {

        $output = "";

        foreach($nodes as $node) {

            if (!isset($node[200]['{' . Sabre_CardDAV_Plugin::NS_CARDDAV . '}address-data'])) {
                continue;
            }
            $nodeData = $node[200]['{' . Sabre_CardDAV_Plugin::NS_CARDDAV . '}address-data'];

            // Parsing this node so VObject can clean up the output.
            $output .=
               VObject\Reader::read($nodeData)->serialize();

        }

        return $output;

    }

}
