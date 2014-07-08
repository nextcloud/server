<?php

/**
 * ownCloud
 *
 * @author Frank Karlitschek
 * @author Jakob Sack
 * @copyright 2012 Frank Karlitschek frank@owncloud.org
 * @copyright 2011 Jakob Sack kde@jakobsack.de
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
// load needed apps
$RUNTIME_APPTYPES = array('filesystem', 'authentication', 'logging');

OC_App::loadApps($RUNTIME_APPTYPES);

OC_Util::obEnd();

// Backends
$authBackend = new OC_Connector_Sabre_Auth();
$lockBackend = new OC_Connector_Sabre_Locks();
$requestBackend = new OC_Connector_Sabre_Request();

// Create ownCloud Dir
$rootDir = new OC_Connector_Sabre_Directory('');
$objectTree = new \OC\Connector\Sabre\ObjectTree($rootDir);

// Fire up server
$server = new OC_Connector_Sabre_Server($objectTree);
$server->httpRequest = $requestBackend;
$server->setBaseUri($baseuri);

// Load plugins
$defaults = new OC_Defaults();
$server->addPlugin(new Sabre_DAV_Auth_Plugin($authBackend, $defaults->getName()));
$server->addPlugin(new Sabre_DAV_Locks_Plugin($lockBackend));
$server->addPlugin(new Sabre_DAV_Browser_Plugin(false)); // Show something in the Browser, but no upload
$server->addPlugin(new OC_Connector_Sabre_FilesPlugin());
$server->addPlugin(new OC_Connector_Sabre_QuotaPlugin());
$server->addPlugin(new OC_Connector_Sabre_MaintenancePlugin());
$server->addPlugin(new OC_Connector_Sabre_ExceptionLoggerPlugin('webdav'));

// And off we go!
$server->exec();
