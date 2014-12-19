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

// Backends
$authBackend = new OC_Connector_Sabre_Auth();
$lockBackend = new OC_Connector_Sabre_Locks();
$requestBackend = new OC_Connector_Sabre_Request();

// Fire up server
$objectTree = new \OC\Connector\Sabre\ObjectTree();
$server = new OC_Connector_Sabre_Server($objectTree);
$server->httpRequest = $requestBackend;
$server->setBaseUri($baseuri);

// Load plugins
$defaults = new OC_Defaults();
$server->addPlugin(new \Sabre\DAV\Auth\Plugin($authBackend, $defaults->getName()));
$server->addPlugin(new \Sabre\DAV\Locks\Plugin($lockBackend));
$server->addPlugin(new \Sabre\DAV\Browser\Plugin(false, false)); // Show something in the Browser, but no upload
$server->addPlugin(new OC_Connector_Sabre_FilesPlugin());
$server->addPlugin(new OC_Connector_Sabre_MaintenancePlugin());
$server->addPlugin(new OC_Connector_Sabre_ExceptionLoggerPlugin('webdav'));

// wait with registering these until auth is handled and the filesystem is setup
$server->subscribeEvent('beforeMethod', function () use ($server, $objectTree) {
	$view = \OC\Files\Filesystem::getView();
	$rootInfo = $view->getFileInfo('');

	// Create ownCloud Dir
	$mountManager = \OC\Files\Filesystem::getMountManager();
	$rootDir = new OC_Connector_Sabre_Directory($view, $rootInfo);
	$objectTree->init($rootDir, $view, $mountManager);

	$server->addPlugin(new \OC\Connector\Sabre\TagsPlugin($objectTree, \OC::$server->getTagManager()));
	$server->addPlugin(new OC_Connector_Sabre_QuotaPlugin($view));
}, 30); // priority 30: after auth (10) and acl(20), before lock(50) and handling the request

// And off we go!
$server->exec();
