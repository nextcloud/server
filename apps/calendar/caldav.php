<?php
/**
 * Copyright (c) 2011 Jakob Sack <mail@jakobsack.de>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

// Do not load FS ...
$RUNTIME_NOSETUPFS = true;

require_once('../../lib/base.php');
OC_Util::checkAppEnabled('calendar');

// Backends
$authBackend = new OC_Connector_Sabre_Auth();
$principalBackend = new OC_Connector_Sabre_Principal();
$caldavBackend    = new OC_Connector_Sabre_CalDAV();

// Root nodes
$nodes = array(
	new Sabre_DAVACL_PrincipalCollection($principalBackend),
	new Sabre_CalDAV_CalendarRootNode($principalBackend, $caldavBackend),
);

// Fire up server
$server = new Sabre_DAV_Server($nodes);
$server->setBaseUri(OC::$WEBROOT.'/apps/calendar/caldav.php');
// Add plugins
$server->addPlugin(new Sabre_DAV_Auth_Plugin($authBackend,'ownCloud'));
$server->addPlugin(new Sabre_CalDAV_Plugin());
$server->addPlugin(new Sabre_DAVACL_Plugin());
$server->addPlugin(new Sabre_DAV_Browser_Plugin(false)); // Show something in the Browser, but no upload

// And off we go!
$server->exec();
