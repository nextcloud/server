<?php
/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

if (OCA\Files_Sharing\Helper::isOutgoingServer2serverShareEnabled() === false) {
	return false;
}

// load needed apps
$RUNTIME_APPTYPES = array('filesystem', 'authentication', 'logging');

OC_App::loadApps($RUNTIME_APPTYPES);

OC_Util::obEnd();

// Backends
$authBackend = new OCA\Files_Sharing\Connector\PublicAuth(\OC::$server->getConfig());

// Fire up server
$objectTree = new \OC\Connector\Sabre\ObjectTree();
$server = new \OC\Connector\Sabre\Server($objectTree);
// Set URL explicitly due to reverse-proxy situations
$server->httpRequest->setUrl(\OC::$server->getRequest()->getRequestUri());
$server->setBaseUri($baseuri);

// Load plugins
$defaults = new OC_Defaults();
$server->addPlugin(new \Sabre\DAV\Auth\Plugin($authBackend, $defaults->getName()));
$server->addPlugin(new \Sabre\DAV\Browser\Plugin(false)); // Show something in the Browser, but no upload
$server->addPlugin(new \OC\Connector\Sabre\FilesPlugin($objectTree));
$server->addPlugin(new \OC\Connector\Sabre\MaintenancePlugin());
$server->addPlugin(new \OC\Connector\Sabre\ExceptionLoggerPlugin('webdav', \OC::$server->getLogger()));

// wait with registering these until auth is handled and the filesystem is setup
$server->on('beforeMethod', function () use ($server, $objectTree, $authBackend) {
	$share = $authBackend->getShare();
	$owner = $share['uid_owner'];
	$isWritable = $share['permissions'] & (\OCP\Constants::PERMISSION_UPDATE | \OCP\Constants::PERMISSION_CREATE);
	$fileId = $share['file_source'];

	if (!$isWritable) {
		\OC\Files\Filesystem::addStorageWrapper('readonly', function ($mountPoint, $storage) {
			return new \OCA\Files_Sharing\ReadOnlyWrapper(array('storage' => $storage));
		});
	}

	OC_Util::setupFS($owner);
	$ownerView = \OC\Files\Filesystem::getView();
	$path = $ownerView->getPath($fileId);

	$view = new \OC\Files\View($ownerView->getAbsolutePath($path));
	$rootInfo = $view->getFileInfo('');

	// Create ownCloud Dir
	if ($rootInfo->getType() === 'dir') {
		$root = new \OC\Connector\Sabre\Directory($view, $rootInfo);
	} else {
		$root = new \OC\Connector\Sabre\File($view, $rootInfo);
	}
	$mountManager = \OC\Files\Filesystem::getMountManager();
	$objectTree->init($root, $view, $mountManager);

	$server->addPlugin(new \OC\Connector\Sabre\QuotaPlugin($view));
}, 30); // priority 30: after auth (10) and acl(20), before lock(50) and handling the request

// And off we go!
$server->exec();
