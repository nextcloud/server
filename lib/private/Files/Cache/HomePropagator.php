<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Files\Cache;

use OCP\IDBConnection;

class HomePropagator extends Propagator {
	private $ignoredBaseFolders;

	/**
	 * @param \OC\Files\Storage\Storage $storage
	 */
	public function __construct(\OC\Files\Storage\Storage $storage, IDBConnection $connection) {
		parent::__construct($storage, $connection);
		$this->ignoredBaseFolders = ['files_encryption'];
	}


	/**
	 * @param string $internalPath
	 * @param int $time
	 * @param int $sizeDifference number of bytes the file has grown
	 */
	public function propagateChange($internalPath, $time, $sizeDifference = 0) {
		[$baseFolder] = explode('/', $internalPath, 2);
		if (in_array($baseFolder, $this->ignoredBaseFolders)) {
			return [];
		} else {
			parent::propagateChange($internalPath, $time, $sizeDifference);
		}
	}
}
