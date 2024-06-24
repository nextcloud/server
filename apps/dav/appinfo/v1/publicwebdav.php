<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

use OCP\BeforeSabrePubliclyLoadedEvent;
use OCP\EventDispatcher\IEventDispatcher;
use Psr\Log\LoggerInterface;

// load needed apps
$RUNTIME_APPTYPES = ['filesystem', 'authentication', 'logging'];

OC_App::loadApps($RUNTIME_APPTYPES);

OC_Util::obEnd();
\OC::$server->getSession()->close();

// Backends
$authBackend = new OCA\DAV\Connector\LegacyPublicAuth(
	\OC::$server->getRequest(),
	\OC::$server->getShareManager(),
	\OC::$server->getSession(),
	\OC::$server->getBruteForceThrottler()
);
$authPlugin = new \Sabre\DAV\Auth\Plugin($authBackend);

/** @var IEventDispatcher $eventDispatcher */
$eventDispatcher = \OC::$server->get(IEventDispatcher::class);

$serverFactory = new OCA\DAV\Connector\Sabre\ServerFactory(
	\OC::$server->getConfig(),
	\OC::$server->get(LoggerInterface::class),
	\OC::$server->getDatabaseConnection(),
	\OC::$server->getUserSession(),
	\OC::$server->getMountManager(),
	\OC::$server->getTagManager(),
	\OC::$server->getRequest(),
	\OC::$server->getPreviewManager(),
	$eventDispatcher,
	\OC::$server->getL10N('dav')
);

$requestUri = \OC::$server->getRequest()->getRequestUri();

$linkCheckPlugin = new \OCA\DAV\Files\Sharing\PublicLinkCheckPlugin();
$filesDropPlugin = new \OCA\DAV\Files\Sharing\FilesDropPlugin();

$server = $serverFactory->createServer($baseuri, $requestUri, $authPlugin, function (\Sabre\DAV\Server $server) use ($authBackend, $linkCheckPlugin, $filesDropPlugin) {
	$isAjax = in_array('XMLHttpRequest', explode(',', $_SERVER['HTTP_X_REQUESTED_WITH'] ?? ''));
	/** @var \OCA\FederatedFileSharing\FederatedShareProvider $shareProvider */
	$federatedShareProvider = \OC::$server->query(\OCA\FederatedFileSharing\FederatedShareProvider::class);
	if ($federatedShareProvider->isOutgoingServer2serverShareEnabled() === false && !$isAjax) {
		// this is what is thrown when trying to access a non-existing share
		throw new \Sabre\DAV\Exception\NotAuthenticated();
	}

	$share = $authBackend->getShare();
	$owner = $share->getShareOwner();
	$isReadable = $share->getPermissions() & \OCP\Constants::PERMISSION_READ;
	$fileId = $share->getNodeId();

	// FIXME: should not add storage wrappers outside of preSetup, need to find a better way
	$previousLog = \OC\Files\Filesystem::logWarningWhenAddingStorageWrapper(false);
	\OC\Files\Filesystem::addStorageWrapper('sharePermissions', function ($mountPoint, $storage) use ($share) {
		return new \OC\Files\Storage\Wrapper\PermissionsMask(['storage' => $storage, 'mask' => $share->getPermissions() | \OCP\Constants::PERMISSION_SHARE]);
	});
	\OC\Files\Filesystem::addStorageWrapper('shareOwner', function ($mountPoint, $storage) use ($share) {
		return new \OCA\DAV\Storage\PublicOwnerWrapper(['storage' => $storage, 'owner' => $share->getShareOwner()]);
	});
	\OC\Files\Filesystem::logWarningWhenAddingStorageWrapper($previousLog);

	$rootFolder = \OCP\Server::get(\OCP\Files\IRootFolder::class);
	$userFolder = $rootFolder->getUserFolder($owner);
	$node = $userFolder->getFirstNodeById($fileId);
	if (!$node) {
		throw new \Sabre\DAV\Exception\NotFound();
	}
	$linkCheckPlugin->setFileInfo($node);

	// If not readable (files_drop) enable the filesdrop plugin
	if (!$isReadable) {
		$filesDropPlugin->enable();
	}

	$view = new \OC\Files\View($node->getPath());
	$filesDropPlugin->setView($view);

	return $view;
});

$server->addPlugin($linkCheckPlugin);
$server->addPlugin($filesDropPlugin);
// allow setup of additional plugins
$event = new BeforeSabrePubliclyLoadedEvent($server);
$eventDispatcher->dispatchTyped($event);

// And off we go!
$server->exec();
