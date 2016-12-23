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
	 * @return IPrincipal
	 */
	function getChildForPrincipal(array $principalInfo) {
		return new AvatarHome($principalInfo);
	}

	function getName() {
		return 'avatars';
	}

}
