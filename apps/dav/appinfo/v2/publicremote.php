<?php

/**
 * SPDX-FileCopyrightText: 2020-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

use OC\Files\Filesystem;
use OC\Files\Storage\Wrapper\PermissionsMask;
use OC\Files\View;
use OCA\DAV\Storage\PublicOwnerWrapper;
use OCA\FederatedFileSharing\FederatedShareProvider;
use OCP\EventDispatcher\IEventDispatcher;
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
use OCP\Share\IManager;
use Psr\Log\LoggerInterface;
use Sabre\DAV\Exception\NotAuthenticated;
use Sabre\DAV\Exception\NotFound;

// load needed apps
$RUNTIME_APPTYPES = ['filesystem', 'authentication', 'logging'];
OC_App::loadApps($RUNTIME_APPTYPES);
OC_Util::obEnd();

$session = \OCP\Server::get(ISession::class);
$request = \OCP\Server::get(IRequest::class);

$session->close();
$requestUri = $request->getRequestUri();

// Backends
$authBackend = new OCA\DAV\Connector\Sabre\PublicAuth(
	$request,
	\OCP\Server::get(IManager::class),
	$session,
	\OCP\Server::get(IThrottler::class),
	\OCP\Server::get(LoggerInterface::class)
);
$authPlugin = new \Sabre\DAV\Auth\Plugin($authBackend);

$l10nFactory = \OCP\Server::get(IFactory::class);
$serverFactory = new OCA\DAV\Connector\Sabre\ServerFactory(
	\OCP\Server::get(IConfig::class),
	\OCP\Server::get(LoggerInterface::class),
	\OCP\Server::get(IDBConnection::class),
	\OCP\Server::get(IUserSession::class),
	\OCP\Server::get(IMountManager::class),
	\OCP\Server::get(ITagManager::class),
	$request,
	\OCP\Server::get(IPreview::class),
	\OCP\Server::get(IEventDispatcher::class),
	$l10nFactory->get('dav'),
);


$linkCheckPlugin = new \OCA\DAV\Files\Sharing\PublicLinkCheckPlugin();
$filesDropPlugin = new \OCA\DAV\Files\Sharing\FilesDropPlugin();

// Define root url with /public.php/dav/files/TOKEN
/** @var string $baseuri defined in public.php */
preg_match('/(^files\/\w+)/i', substr($requestUri, strlen($baseuri)), $match);
$baseuri = $baseuri . $match[0];

$server = $serverFactory->createServer($baseuri, $requestUri, $authPlugin, function (\Sabre\DAV\Server $server) use ($authBackend, $linkCheckPlugin, $filesDropPlugin) {
	$isAjax = in_array('XMLHttpRequest', explode(',', $_SERVER['HTTP_X_REQUESTED_WITH'] ?? ''));
	$federatedShareProvider = \OCP\Server::get(FederatedShareProvider::class);
	if ($federatedShareProvider->isOutgoingServer2serverShareEnabled() === false && !$isAjax) {
		// this is what is thrown when trying to access a non-existing share
		throw new NotAuthenticated();
	}

	$share = $authBackend->getShare();
	$owner = $share->getShareOwner();
	$isReadable = $share->getPermissions() & \OCP\Constants::PERMISSION_READ;
	$fileId = $share->getNodeId();

	// FIXME: should not add storage wrappers outside of preSetup, need to find a better way
	/** @psalm-suppress InternalMethod */
	$previousLog = Filesystem::logWarningWhenAddingStorageWrapper(false);

	/** @psalm-suppress MissingClosureParamType */
	Filesystem::addStorageWrapper('sharePermissions', function ($mountPoint, $storage) use ($share) {
		return new PermissionsMask(['storage' => $storage, 'mask' => $share->getPermissions() | \OCP\Constants::PERMISSION_SHARE]);
	});

	/** @psalm-suppress MissingClosureParamType */
	Filesystem::addStorageWrapper('shareOwner', function ($mountPoint, $storage) use ($share) {
		return new PublicOwnerWrapper(['storage' => $storage, 'owner' => $share->getShareOwner()]);
	});

	/** @psalm-suppress InternalMethod */
	Filesystem::logWarningWhenAddingStorageWrapper($previousLog);

	$rootFolder = \OCP\Server::get(\OCP\Files\IRootFolder::class);
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

	$view = new View($node->getPath());
	$filesDropPlugin->setView($view);

	return $view;
});

$server->addPlugin($linkCheckPlugin);
$server->addPlugin($filesDropPlugin);

// And off we go!
$server->exec();
