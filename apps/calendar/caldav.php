<?php
/**
 * ownCloud - Addressbook
 *
 * @author Jakob Sack
 * @copyright 2011 Jakob Sack mail@jakobsack.de
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

// Do not load FS ...
$RUNTIME_NOSETUPFS = true;

require_once('../../lib/base.php');

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
$server->setBaseUri($WEBROOT.'/apps/calendar/caldav.php');
// Add plugins
$server->addPlugin(new Sabre_DAV_Auth_Plugin($authBackend,'ownCloud'));
$server->addPlugin(new Sabre_CalDAV_Plugin());
$server->addPlugin(new Sabre_DAVACL_Plugin());

// And off we go!
$server->exec();
