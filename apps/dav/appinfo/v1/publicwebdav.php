<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Bjoern Schiessle <bjoern@schiessle.org>
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Vincent Petry <pvince81@owncloud.com>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

// load needed apps
$RUNTIME_APPTYPES = ['filesystem', 'authentication', 'logging'];

OC_App::loadApps($RUNTIME_APPTYPES);

OC_Util::obEnd();
\OC::$server->getSession()->close();

// Backends
$authBackend = new OCA\DAV\Connector\PublicAuth(
	\OC::$server->getRequest(),
	\OC::$server->getShareManager(),
	\OC::$server->getSession()
);

$serverFactory = new OCA\DAV\Connector\Sabre\ServerFactory(
	\OC::$server->getConfig(),
	\OC::$server->getLogger(),
	\OC::$server->getDatabaseConnection(),
	\OC::$server->getUserSession(),
	\OC::$server->getMountManager(),
	\OC::$server->getTagManager(),
	\OC::$server->getRequest(),
	\OC::$server->getPreviewManager()
);

$requestUri = \OC::$server->getRequest()->getRequestUri();

$linkCheckPlugin = new \OCA\DAV\Files\Sharing\PublicLinkCheckPlugin();

$server = $serverFactory->createServer($baseuri, $requestUri, $authBackend, function (\Sabre\DAV\Server $server) use ($authBackend, $linkCheckPlugin) {
	$isAjax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest');
	$federatedSharingApp = new \OCA\FederatedFileSharing\AppInfo\Application();
	$federatedShareProvider = $federatedSharingApp->getFederatedShareProvider();
	if ($federatedShareProvider->isOutgoingServer2serverShareEnabled() === false && !$isAjax) {
		// this is what is thrown when trying to access a non-existing share
		throw new \Sabre\DAV\Exception\NotAuthenticated();
	}

	$share = $authBackend->getShare();
	$owner = $share->getShareOwner();
	$isReadable = $share->getPermissions() & \OCP\Constants::PERMISSION_READ;
	$fileId = $share->getNodeId();

	if (!$isReadable) {
		return false;
	}

	// FIXME: should not add storage wrappers outside of preSetup, need to find a better way
	$previousLog = \OC\Files\Filesystem::logWarningWhenAddingStorageWrapper(false);
	\OC\Files\Filesystem::addStorageWrapper('sharePermissions', function ($mountPoint, $storage) use ($share) {
		return new \OC\Files\Storage\Wrapper\PermissionsMask(array('storage' => $storage, 'mask' => $share->getPermissions() | \OCP\Constants::PERMISSION_SHARE));
	});
	\OC\Files\Filesystem::logWarningWhenAddingStorageWrapper($previousLog);

	OC_Util::setupFS($owner);
	$ownerView = \OC\Files\Filesystem::getView();
	$path = $ownerView->getPath($fileId);
	$fileInfo = $ownerView->getFileInfo($path);
	$linkCheckPlugin->setFileInfo($fileInfo);

	return new \OC\Files\View($ownerView->getAbsolutePath($path));
});

$server->addPlugin($linkCheckPlugin);

// And off we go!
$server->exec();
