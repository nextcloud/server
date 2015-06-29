<?php
/**
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Frank Karlitschek <frank@owncloud.org>
 * @author Jakob Sack <mail@jakobsack.de>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Lukas Reschke <lukas@owncloud.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Vincent Petry <pvince81@owncloud.com>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

// no php execution timeout for webdav
set_time_limit(0);

// Turn off output buffering to prevent memory problems
\OC_Util::obEnd();

// Backends
$authBackend = new \OC\Connector\Sabre\Auth();

// Fire up server
$objectTree = new \OC\Connector\Sabre\ObjectTree();
$server = new \OC\Connector\Sabre\Server($objectTree);
// Set URL explicitly due to reverse-proxy situations
$server->httpRequest->setUrl(\OC::$server->getRequest()->getRequestUri());
$server->setBaseUri($baseuri);

// Load plugins
$defaults = new OC_Defaults();
$server->addPlugin(new \OC\Connector\Sabre\BlockLegacyClientPlugin(\OC::$server->getConfig()));
$server->addPlugin(new \Sabre\DAV\Auth\Plugin($authBackend, $defaults->getName()));
// FIXME: The following line is a workaround for legacy components relying on being able to send a GET to /
$server->addPlugin(new \OC\Connector\Sabre\DummyGetResponsePlugin());
$server->addPlugin(new \OC\Connector\Sabre\FilesPlugin($objectTree));
$server->addPlugin(new \OC\Connector\Sabre\MaintenancePlugin(\OC::$server->getConfig()));
$server->addPlugin(new \OC\Connector\Sabre\ExceptionLoggerPlugin('webdav', \OC::$server->getLogger()));

// wait with registering these until auth is handled and the filesystem is setup
$server->on('beforeMethod', function () use ($server, $objectTree) {
	$view = \OC\Files\Filesystem::getView();
	$rootInfo = $view->getFileInfo('');

	// Create ownCloud Dir
	$mountManager = \OC\Files\Filesystem::getMountManager();
	$rootDir = new \OC\Connector\Sabre\Directory($view, $rootInfo);
	$objectTree->init($rootDir, $view, $mountManager);

	$server->addPlugin(new \OC\Connector\Sabre\TagsPlugin($objectTree, \OC::$server->getTagManager()));
	$server->addPlugin(new \OC\Connector\Sabre\QuotaPlugin($view));

	// custom properties plugin must be the last one
	$server->addPlugin(
		new \Sabre\DAV\PropertyStorage\Plugin(
			new \OC\Connector\Sabre\CustomPropertiesBackend(
				$objectTree,
				\OC::$server->getDatabaseConnection(),
				\OC::$server->getUserSession()->getUser()
			)
		)
	);
	$server->addPlugin(new \OC\Connector\Sabre\CopyEtagHeaderPlugin());
}, 30); // priority 30: after auth (10) and acl(20), before lock(50) and handling the request

// And off we go!
$server->exec();
