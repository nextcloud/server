<?php

namespace OCA\DAV\Avatars;

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
		$avatarManager = \OC::$server->getAvatarManager();
		return new AvatarHome($principalInfo, $avatarManager);
	}

	public function getName() {
		return 'avatars';
	}
}
