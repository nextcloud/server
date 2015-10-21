<?php

namespace OCA\DAV\Files;

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
		return new FilesHome($principalInfo);
	}

	function getName() {
		return 'files';
	}

}
