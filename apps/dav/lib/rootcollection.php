<?php

namespace OCA\DAV;

use OCA\DAV\Connector\Sabre\Principal;
use Sabre\CalDAV\Principal\Collection;
use Sabre\DAV\SimpleCollection;

class RootCollection extends SimpleCollection {

	public function __construct() {
		$config = \OC::$server->getConfig();
		$principalBackend = new Principal(
			$config,
			\OC::$server->getUserManager()
		);
		// as soon as debug mode is enabled we allow listing of principals
		$disableListing = !$config->getSystemValue('debug', false);

		// setup the first level of the dav tree
		$principalCollection = new Collection($principalBackend);
		$principalCollection->disableListing = $disableListing;
		$filesCollection = new Files\RootCollection($principalBackend);
		$filesCollection->disableListing = $disableListing;

		$children = [
			$principalCollection,
			$filesCollection,
		];

		parent::__construct('root', $children);
	}

}
