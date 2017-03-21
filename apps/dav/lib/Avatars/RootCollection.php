<?php

namespace OCA\DAV\Avatars;

use Sabre\DAVACL\AbstractPrincipalCollection;
use Sabre\DAVACL\IPrincipal;

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
	function getChildForPrincipal(array $principalInfo) {
		$avatarManager = \OC::$server->getAvatarManager();
		return new AvatarHome($principalInfo, $avatarManager);
	}

	function getName() {
		return 'avatars';
	}

}
