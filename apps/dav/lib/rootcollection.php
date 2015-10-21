<?php

namespace OCA\DAV;

use OCA\DAV\Connector\Sabre\Principal;
use Sabre\CalDAV\Principal\Collection;
use Sabre\DAV\SimpleCollection;

class RootCollection extends SimpleCollection {

	public function __construct() {
		$principalBackend = new Principal(
			\OC::$server->getConfig(),
			\OC::$server->getUserManager()
		);
		$principalCollection = new Collection($principalBackend);
		$principalCollection->disableListing = true;
		$filesCollection = new Files\RootCollection($principalBackend);
		$filesCollection->disableListing = true;

		$children = [
			$principalCollection,
			$filesCollection,
		];

		parent::__construct('root', $children);
	}

}
