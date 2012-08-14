<?php
/**
 * Copyright (c) 2011 Jakob Sack <mail@jakobsack.de>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
OCP\App::checkAppEnabled('calendar');

if(substr($_SERVER["REQUEST_URI"],0,strlen(OC::$APPSWEBROOT . '/apps/calendar/caldav.php')) == OC::$APPSWEBROOT . '/apps/calendar/caldav.php'){
	$baseuri = OC::$APPSWEBROOT . '/apps/calendar/caldav.php';
}

// only need authentication apps
$RUNTIME_APPTYPES=array('authentication');
OC_App::loadApps($RUNTIME_APPTYPES);

// Backends
$authBackend = new OC_Connector_Sabre_Auth();
$principalBackend = new OC_Connector_Sabre_Principal();
$caldavBackend    = new OC_Connector_Sabre_CalDAV();

// Root nodes
$Sabre_CalDAV_Principal_Collection = new Sabre_CalDAV_Principal_Collection($principalBackend); 
$Sabre_CalDAV_Principal_Collection->disableListing = true; // Disable listening

$Sabre_CalDAV_CalendarRootNode = new Sabre_CalDAV_CalendarRootNode($principalBackend, $caldavBackend); 
$Sabre_CalDAV_CalendarRootNode->disableListing = true; // Disable listening

$nodes = array( 
	$Sabre_CalDAV_Principal_Collection, 
	$Sabre_CalDAV_CalendarRootNode,
	);


// Fire up server
$server = new Sabre_DAV_Server($nodes);
$server->setBaseUri($baseuri);
// Add plugins
$server->addPlugin(new Sabre_DAV_Auth_Plugin($authBackend,'ownCloud'));
$server->addPlugin(new Sabre_CalDAV_Plugin());
$server->addPlugin(new Sabre_DAVACL_Plugin());
$server->addPlugin(new Sabre_DAV_Browser_Plugin(false)); // Show something in the Browser, but no upload

// And off we go!
$server->exec();
