<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Bjoern Schiessle <bjoern@schiessle.org>
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Vincent Petry <vincent@nextcloud.com>
 *
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
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

	OC_Util::tearDownFS();
	OC_Util::setupFS($owner);
	$ownerView = new View('/'. $owner . '/files');
	$path = $ownerView->getPath($fileId);
	$fileInfo = $ownerView->getFileInfo($path);

	if ($fileInfo === false) {
		throw new NotFound();
	}

	$linkCheckPlugin->setFileInfo($fileInfo);

	// If not readble (files_drop) enable the filesdrop plugin
	if (!$isReadable) {
		$filesDropPlugin->enable();
	}

	$view = new View($ownerView->getAbsolutePath($path));
	$filesDropPlugin->setView($view);

	return $view;
});

$server->addPlugin($linkCheckPlugin);
$server->addPlugin($filesDropPlugin);

// And off we go!
$server->exec();
