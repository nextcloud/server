<?php
/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

// load needed apps
$RUNTIME_APPTYPES = array('filesystem', 'authentication', 'logging');

OC_App::loadApps($RUNTIME_APPTYPES);

OC_Util::obEnd();

// Backends
$authBackend = new OCA\Files_Sharing\Connector\PublicAuth(\OC::$server->getConfig());
$lockBackend = new OC_Connector_Sabre_Locks();
$requestBackend = new OC_Connector_Sabre_Request();

// Fire up server
$objectTree = new \OC\Connector\Sabre\ObjectTree();
$server = new OC_Connector_Sabre_Server($objectTree);
$server->httpRequest = $requestBackend;
$server->setBaseUri($baseuri);

// Load plugins
$defaults = new OC_Defaults();
$server->addPlugin(new Sabre_DAV_Auth_Plugin($authBackend, $defaults->getName()));
$server->addPlugin(new Sabre_DAV_Locks_Plugin($lockBackend));
$server->addPlugin(new Sabre_DAV_Browser_Plugin(false)); // Show something in the Browser, but no upload
$server->addPlugin(new OC_Connector_Sabre_FilesPlugin());
$server->addPlugin(new OC_Connector_Sabre_MaintenancePlugin());
$server->addPlugin(new OC_Connector_Sabre_ExceptionLoggerPlugin('webdav'));

// wait with registering these until auth is handled and the filesystem is setup
$server->subscribeEvent('beforeMethod', function () use ($server, $objectTree, $authBackend) {
	$share = $authBackend->getShare();
	$owner = $share['uid_owner'];
	$fileId = $share['file_source'];
	OC_Util::setupFS($owner);
	$ownerView = \OC\Files\Filesystem::getView();
	$path = $ownerView->getPath($fileId);


	$view = new \OC\Files\View($ownerView->getAbsolutePath($path));
	$rootInfo = $view->getFileInfo('');

	// Create ownCloud Dir
	$rootDir = new OC_Connector_Sabre_Directory($view, $rootInfo);
	$objectTree->init($rootDir, $view);

	$server->addPlugin(new OC_Connector_Sabre_AbortedUploadDetectionPlugin($view));
	$server->addPlugin(new OC_Connector_Sabre_QuotaPlugin($view));
}, 30); // priority 30: after auth (10) and acl(20), before lock(50) and handling the request

// And off we go!
$server->exec();
