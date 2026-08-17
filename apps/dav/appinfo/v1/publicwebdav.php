<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
use OC\Files\Filesystem;
use OC\Files\Storage\Wrapper\DirPermissionsMask;
use OC\Files\Storage\Wrapper\PermissionsMask;
use OC\Files\View;
use OCA\DAV\Connector\LegacyPublicAuth;
use OCA\DAV\Connector\Sabre\ServerFactory;
use OCA\DAV\Files\Sharing\FilesDropPlugin;
use OCA\DAV\Files\Sharing\PublicLinkCheckPlugin;
use OCA\DAV\Storage\PublicOwnerWrapper;
use OCA\FederatedFileSharing\FederatedShareProvider;
use OCP\App\IAppManager;
use OCP\BeforeSabrePubliclyLoadedEvent;
use OCP\Constants;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\IHomeStorage;
use OCP\Files\IRootFolder;
use OCP\Files\Mount\IMountManager;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IPreview;
use OCP\IRequest;
use OCP\ISession;
use OCP\ITagManager;
use OCP\IUserSession;
use OCP\L10N\IFactory as IL10nFactory;
use OCP\Security\Bruteforce\IThrottler;
use OCP\Server;
use Psr\Log\LoggerInterface;

// load needed apps
$RUNTIME_APPTYPES = ['filesystem', 'authentication', 'logging'];
Server::get(IAppManager::class)->loadApps($RUNTIME_APPTYPES);

// Turn off output buffering to prevent memory problems
while (ob_get_level()) {
	ob_end_clean();
}
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
	Server::get(IL10nFactory::class)->get('dav')
);

$requestUri = Server::get(IRequest::class)->getRequestUri();

$linkCheckPlugin = new PublicLinkCheckPlugin();
$filesDropPlugin = new FilesDropPlugin();

/** @var string $baseuri defined in public.php */
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
		$isReadable = $share->getPermissions() & Constants::PERMISSION_READ;
		$fileId = $share->getNodeId();

		// FIXME: should not add storage wrappers outside of preSetup, need to find a better way
		$previousLog = Filesystem::logWarningWhenAddingStorageWrapper(false);
		Filesystem::addStorageWrapper('sharePermissions', function ($mountPoint, $storage) use ($share) {
			$mask = $share->getPermissions() | Constants::PERMISSION_SHARE;

			if ($storage instanceof IHomeStorage) {
				return new DirPermissionsMask([
					'storage' => $storage,
					'mask' => $mask,
					'path' => 'files',
				]);
			} else {
				return new PermissionsMask(['storage' => $storage, 'mask' => $mask]);
			}
		});
		Filesystem::addStorageWrapper('shareOwner', function ($mountPoint, $storage) use ($share) {
			return new PublicOwnerWrapper(['storage' => $storage, 'owner' => $share->getShareOwner()]);
		});
		Filesystem::logWarningWhenAddingStorageWrapper($previousLog);

		$rootFolder = Server::get(IRootFolder::class);
		$sharedBy = $share->getSharedBy();
		if ($share->getShareType() === \OCP\Share\IShare::TYPE_REMOTE) {
			$sharedBy = $share->getShareOwner();
		}
		$userFolder = $rootFolder->getUserFolder($sharedBy);
		$node = $userFolder->getFirstNodeById($fileId);
		if (!$node) {
			throw new \Sabre\DAV\Exception\NotFound();
		}

		// getFirstNodeById might return a node without share permission -> try to find a node which is shareable
		if (!$node->isShareable()) {
			foreach ($userFolder->getById($fileId) as $candidate) {
				if ($candidate->isShareable()) {
					$node = $candidate;
					break;
				}
			}
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
$server->start();
