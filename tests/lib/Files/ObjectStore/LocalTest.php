<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Files\ObjectStore;

use OC\Files\ObjectStore\StorageObjectStore;
use OC\Files\Storage\Temporary;

class LocalTest extends ObjectStoreTest {
	/**
	 * @return \OCP\Files\ObjectStore\IObjectStore
	 */
	protected function getInstance() {
		$storage = new Temporary();
		return new StorageObjectStore($storage);
	}
}
