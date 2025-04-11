<?php

/**
 * SPDX-FileCopyrightText: 2020-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
use OC\Files\Filesystem;
use OC\Files\Storage\Wrapper\PermissionsMask;
use OC\Files\View;
use OCA\DAV\Connector\Sabre\PublicAuth;
use OCA\DAV\Connector\Sabre\ServerFactory;
use OCA\DAV\Files\Sharing\FilesDropPlugin;
use OCA\DAV\Files\Sharing\PublicLinkCheckPlugin;
use OCA\DAV\Storage\PublicOwnerWrapper;
use OCA\DAV\Storage\PublicShareWrapper;
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

// load needed apps
$RUNTIME_APPTYPES = ['filesystem', 'authentication', 'logging'];
OC_App::loadApps($RUNTIME_APPTYPES);
OC_Util::obEnd();

$session = Server::get(ISession::class);
$request = Server::get(IRequest::class);
$eventDispatcher = Server::get(IEventDispatcher::class);

$session->close();
$requestUri = $request->getRequestUri();

// Backends
$authBackend = new PublicAuth(
	$request,
	Server::get(IManager::class),
	$session,
	Server::get(IThrottler::class),
	Server::get(LoggerInterface::class)
);
$authPlugin = new \Sabre\DAV\Auth\Plugin($authBackend);

$l10nFactory = Server::get(IFactory::class);
$serverFactory = new ServerFactory(
	Server::get(IConfig::class),
	Server::get(LoggerInterface::class),
	Server::get(IDBConnection::class),
	Server::get(IUserSession::class),
	Server::get(IMountManager::class),
	Server::get(ITagManager::class),
	$request,
	Server::get(IPreview::class),
	$eventDispatcher,
	$l10nFactory->get('dav'),
);


$linkCheckPlugin = new PublicLinkCheckPlugin();
$filesDropPlugin = new FilesDropPlugin();

// Define root url with /public.php/dav/files/TOKEN
/** @var string $baseuri defined in public.php */
preg_match('/(^files\/[a-z0-9-_]+)/i', substr($requestUri, strlen($baseuri)), $match);
$baseuri = $baseuri . $match[0];

$server = $serverFactory->createServer($baseuri, $requestUri, $authPlugin, function (\Sabre\DAV\Server $server) use ($authBackend, $linkCheckPlugin, $filesDropPlugin) {
	// GET must be allowed for e.g. showing images and allowing Zip downloads
	if ($server->httpRequest->getMethod() !== 'GET') {
		// If this is *not* a GET request we only allow access to public DAV from AJAX or when Server2Server is allowed
		$isAjax = in_array('XMLHttpRequest', explode(',', $_SERVER['HTTP_X_REQUESTED_WITH'] ?? ''));
		$federatedShareProvider = Server::get(FederatedShareProvider::class);
		if ($federatedShareProvider->isOutgoingServer2serverShareEnabled() === false && $isAjax === false) {
			// this is what is thrown when trying to access a non-existing share
			throw new NotAuthenticated();
		}
	}

	$share = $authBackend->getShare();
	$owner = $share->getShareOwner();
	$isReadable = $share->getPermissions() & Constants::PERMISSION_READ;
	$fileId = $share->getNodeId();

	// FIXME: should not add storage wrappers outside of preSetup, need to find a better way
	/** @psalm-suppress InternalMethod */
	$previousLog = Filesystem::logWarningWhenAddingStorageWrapper(false);

	/** @psalm-suppress MissingClosureParamType */
	Filesystem::addStorageWrapper('sharePermissions', function ($mountPoint, $storage) use ($share) {
		return new PermissionsMask(['storage' => $storage, 'mask' => $share->getPermissions() | Constants::PERMISSION_SHARE]);
	});

	/** @psalm-suppress MissingClosureParamType */
	Filesystem::addStorageWrapper('shareOwner', function ($mountPoint, $storage) use ($share) {
		return new PublicOwnerWrapper(['storage' => $storage, 'owner' => $share->getShareOwner()]);
	});

	// Ensure that also private shares have the `getShare` method
	/** @psalm-suppress MissingClosureParamType */
	Filesystem::addStorageWrapper('getShare', function ($mountPoint, $storage) use ($share) {
		return new PublicShareWrapper(['storage' => $storage, 'share' => $share]);
	}, 0);

	/** @psalm-suppress InternalMethod */
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

	$view = new View($node->getPath());
	$filesDropPlugin->setView($view);
	$filesDropPlugin->setShare($share);

	return $view;
});

$server->addPlugin($linkCheckPlugin);
$server->addPlugin($filesDropPlugin);

// allow setup of additional plugins
$event = new BeforeSabrePubliclyLoadedEvent($server);
$eventDispatcher->dispatchTyped($event);

// And off we go!
$server->start();
