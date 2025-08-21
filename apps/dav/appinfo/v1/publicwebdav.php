<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2025 Nextcloud GmbH and Nextcloud contributors
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
use OCP\L10N\IFactory;
use OCP\Security\Bruteforce\IThrottler;
use OCP\Server;
use OCP\Share\IManager;
use Psr\Log\LoggerInterface;
use Sabre\DAV\Exception\NotAuthenticated;
use Sabre\DAV\Exception\NotFound;

/**
 * Implements the Public WebDAV server endpoint (v1)
 *
 * - Fires up a WebDAV server for handling a `/public.php/webdav` request.
 * - Loads in the scope of /public.php.
 * - Not to be confused with the v2 public WebDAV server endpoint reachable via `/public.php/dav`.
 *
 * Caveats:
 * - Lacks support for upload chunking (traditional as well as S3 upload chunk streaming).
 * - Only supports Basic Auth with/ Share Token as Username (`LegacyPublicAuth`).
 * - Effectively a legacy endpoint (officially deprecated?)
 */

// Load essential apps
Server::get(IAppManager::class)->loadApps(['authentication', 'filesystem', 'logging']);

// Prevent memory problems if output buffering is enabled (an extra safeguard) 
while (ob_get_level()) {
	ob_end_clean();
}

// Prevent a blocked instance when streaming
Server::get(ISession::class)->close();

// So that we can reference them for $viewCallback
$linkCheckPlugin = new PublicLinkCheckPlugin();
$filesDropPlugin = new FilesDropPlugin();

// Set-up authentication 
$authBackend = new LegacyPublicAuth(
	Server::get(IRequest::class),
	Server::get(IManager::class),
	Server::get(ISession::class),
	Server::get(IThrottler::class)
);
$authPlugin = new \Sabre\DAV\Auth\Plugin($authBackend);

$serverFactory = new ServerFactory(
	Server::get(IConfig::class),
	Server::get(LoggerInterface::class),
	Server::get(IDBConnection::class),
	Server::get(IUserSession::class),
	Server::get(IMountManager::class),
	Server::get(ITagManager::class),
	Server::get(IRequest::class),
	Server::get(IPreview::class),
	Server::get(IEventDispatcher::class),
	Server::get(IFactory::class)->get('dav'), // L10N
);

/**
 * This overly long closure is used to return the View for use by the WebDAV server.
 *
 * It should probably be refactored as it currently:
 *
 * - handles some plugin configuration
 * - does permissions checks
 * - adds storage wrappers
 * - checks for AJAX
 * - checks for outgoing S2S
 */

/** @var callable $viewCallback */
$viewCallback =	function (\Sabre\DAV\Server $server) use (
	$authBackend,
	$linkCheckPlugin,
	$filesDropPlugin
): ?View {
	$isAjax = in_array('XMLHttpRequest', explode(',', $_SERVER['HTTP_X_REQUESTED_WITH'] ?? ''));
	$isOutgoingS2sShareEnabled = Server::get(FederatedShareProvider::class)->isOutgoingServer2serverShareEnabled();

	// Access to this endpoint is only permitted if Outgoing Server2Server Sharing is enabled or via AJAX
	if (!$isOutgoingS2sShareEnabled && !$isAjax) {
		// this is what is thrown when trying to access a non-existing share
		throw new NotAuthenticated();
	}

	$share = $authBackend->getShare();
	$owner = $share->getShareOwner();
	$isReadable = $share->getPermissions() & Constants::PERMISSION_READ;
	$fileId = $share->getNodeId();

	/**
	 * Add storage wrappers (should really not do this here, outside of preSetup)
	 */

	// Temporarily disable warning
	$previousLog = Filesystem::logWarningWhenAddingStorageWrapper(false);

	$sharePermissionsWrapper = function ($mountPoint, $storage) use ($share) {
		return new PermissionsMask(
			['storage' => $storage, 'mask' => $share->getPermissions() | Constants::PERMISSION_SHARE]
		);
	};
	Filesystem::addStorageWrapper('sharePermissions', $sharePermissionsWrapper);

	$shareOwnerWrapper = function ($mountPoint, $storage) use ($share) {
		return new PublicOwnerWrapper(['storage' => $storage, 'owner' => $share->getShareOwner()]);
	};
	Filesystem::addStorageWrapper('shareOwner', $shareOwnerWrapper);

	// Restore prior logging state
	Filesystem::logWarningWhenAddingStorageWrapper($previousLog);

	$rootFolder = Server::get(IRootFolder::class);
	$userFolder = $rootFolder->getUserFolder($owner);
	$node = $userFolder->getFirstNodeById($fileId);
	if (!$node) {
		throw new NotFound();
	}

	$linkCheckPlugin->setFileInfo($node);

	// If not readable (files_drop) enable the filesdrop plugin
	if (!$isReadable) {
		$filesDropPlugin->enable();
	}
	$filesDropPlugin->setShare($share);

	$view = new View($node->getPath());
	return $view;
};

/** var OCA\DAV\Connector\Sabre\Server $server */
$server = $serverFactory->createServer(
	false,
	$baseuri, /** @var string $baseuri defined in remote.php */
	Server::get(IRequest::class)->getRequestUri(),
	$authPlugin,
	$viewCallback,
);

// Note: various "default" plugins are handled within `createServer()` 
$server->addPlugin($linkCheckPlugin);
$server->addPlugin($filesDropPlugin);

// Give apps a chance to inject additional plugins
$event = new BeforeSabrePubliclyLoadedEvent($server);
Server::get(IEventDispatcher::class)->dispatchTyped($event);

// Start the Server!
$server->start();
