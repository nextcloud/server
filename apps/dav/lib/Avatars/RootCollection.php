<?php
/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2017 ownCloud GmbH
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Avatars;

use OCP\IAvatarManager;
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
	 * @return AvatarHome
	 */
	public function getChildForPrincipal(array $principalInfo) {
		$avatarManager = \OC::$server->get(IAvatarManager::class);
		return new AvatarHome($principalInfo, $avatarManager);
	}

	public function getName() {
		return 'avatars';
	}
}
