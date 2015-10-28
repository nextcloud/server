<?php

namespace OCA\DAV\Upload;

use Sabre\DAVACL\AbstractPrincipalCollection;

class RootCollection extends AbstractPrincipalCollection {

	/**
	 * @inheritdoc
	 */
	function getChildForPrincipal(array $principalInfo) {
		return new UploadHome($principalInfo);
	}

	/**
	 * @inheritdoc
	 */
	function getName() {
		return 'uploads';
	}

}
