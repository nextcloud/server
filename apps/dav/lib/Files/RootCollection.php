<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Files;

use OCP\Files\FileInfo;
use OCP\IUserSession;
use OCP\Server;
use Sabre\DAV\INode;
use Sabre\DAV\SimpleCollection;
use Sabre\DAVACL\AbstractPrincipalCollection;

class RootCollection extends AbstractPrincipalCollection {

	/**
	 * This method returns a node for a principal.
	 *
	 * The passed array contains principal information, and is guaranteed to
	 * at least contain a uri item. Other properties may or may not be
	 * supplied by the authentication backend.
	 *
	 * @param array $principalInfo
	 * @return INode
	 */
	public function getChildForPrincipal(array $principalInfo) {
		[,$name] = \Sabre\Uri\split($principalInfo['uri']);
		$user = Server::get(IUserSession::class)->getUser();
		if (is_null($user) || $name !== $user->getUID()) {
			// a user is only allowed to see their own home contents, so in case another collection
			// is accessed, we return a simple empty collection for now
			// in the future this could be considered to be used for accessing shared files
			return new SimpleCollection($name);
		}
		$userFolder = \OC::$server->getUserFolder();
		if (!($userFolder instanceof FileInfo)) {
			throw new \Exception('Home does not exist');
		}
		return new FilesHome($principalInfo, $userFolder);
	}

	public function getName() {
		return 'files';
	}
}
