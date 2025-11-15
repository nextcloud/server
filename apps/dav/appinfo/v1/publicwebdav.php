<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
use OC\Files\Filesystem;
use OC\Files\Storage\Wrapper\PermissionsMask;
use OC\Files\View;
use OCA\DAV\Connector\LegacyPublicAuth;
use OCA\DAV\Connector\Sabre\ServerFactory;
use OCA\DAV\Files\Sharing\FilesDropPlugin;
use OCA\DAV\Files\Sharing\PublicLinkCheckPlugin;
use OCA\DAV\Storage\PublicOwnerWrapper;
use OCA\FederatedFileSharing\FederatedShareProvider;
use OCP\BeforeSabrePubliclyLoadedEvent;
use OCP\Constants;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\IRootFolder;
use OCP\Files\Mount\IMountManager;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IPreview;
use OCP\IRequest;
use OCP\ISession;
use OCP\ITagManager;
use OCP\IUserSession;
use OCP\Security\Bruteforce\IThrottler;
use OCP\Server;
use Psr\Log\LoggerInterface;

// load needed apps
$RUNTIME_APPTYPES = ['filesystem', 'authentication', 'logging'];

OC_App::loadApps($RUNTIME_APPTYPES);

OC_Util::obEnd();
Server::get(ISession::class)->close();

// Backends
$authBackend = new LegacyPublicAuth(
	Server::get(IRequest::class),
	Server::get(\OCP\Share\IManager::class),
	Server::get(ISession::class),
	Server::get(IThrottler::class)
);
$authPlugin = new \Sabre\DAV\Auth\Plugin($authBackend);

/** @var IEventDispatcher $eventDispatcher */
$eventDispatcher = Server::get(IEventDispatcher::class);

$serverFactory = new ServerFactory(
	Server::get(IConfig::class),
	Server::get(LoggerInterface::class),
	Server::get(IDBConnection::class),
	Server::get(IUserSession::class),
	Server::get(IMountManager::class),
	Server::get(ITagManager::class),
	Server::get(IRequest::class),
	Server::get(IPreview::class),
	$eventDispatcher,
	\OC::$server->getL10N('dav')
);

$requestUri = Server::get(IRequest::class)->getRequestUri();

$linkCheckPlugin = new PublicLinkCheckPlugin();
$filesDropPlugin = new FilesDropPlugin();

$server = $serverFactory->createServer(
	true,
	$baseuri,
	$requestUri,
	$authPlugin,
	function (\Sabre\DAV\Server $server) use (
		$authBackend,
		$linkCheckPlugin,
		$filesDropPlugin
	) {
		$isAjax = in_array('XMLHttpRequest', explode(',', $_SERVER['HTTP_X_REQUESTED_WITH'] ?? ''));
		/** @var FederatedShareProvider $shareProvider */
		$federatedShareProvider = Server::get(FederatedShareProvider::class);
		if ($federatedShareProvider->isOutgoingServer2serverShareEnabled() === false && !$isAjax) {
			// this is what is thrown when trying to access a non-existing share
			throw new \Sabre\DAV\Exception\NotAuthenticated();
		}

		$share = $authBackend->getShare();
		$owner = $share->getShareOwner();
		$isReadable = $share->getPermissions() & Constants::PERMISSION_READ;
		$fileId = $share->getNodeId();

		// FIXME: should not add storage wrappers outside of preSetup, need to find a better way
		$previousLog = Filesystem::logWarningWhenAddingStorageWrapper(false);
		Filesystem::addStorageWrapper('sharePermissions', function ($mountPoint, $storage) use ($share) {
			return new PermissionsMask(['storage' => $storage, 'mask' => $share->getPermissions() | Constants::PERMISSION_SHARE]);
		});
		Filesystem::addStorageWrapper('shareOwner', function ($mountPoint, $storage) use ($share) {
			return new PublicOwnerWrapper(['storage' => $storage, 'owner' => $share->getShareOwner()]);
		});
		Filesystem::logWarningWhenAddingStorageWrapper($previousLog);

		$rootFolder = Server::get(IRootFolder::class);
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
		$filesDropPlugin->setShare($share);

		return new View($node->getPath());
	});

$server->addPlugin($linkCheckPlugin);
$server->addPlugin($filesDropPlugin);
// allow setup of additional plugins
$event = new BeforeSabrePubliclyLoadedEvent($server);
$eventDispatcher->dispatchTyped($event);

// And off we go!
$server->exec();
