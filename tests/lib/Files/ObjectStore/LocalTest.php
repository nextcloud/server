<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Files\ObjectStore;

use OC\Files\ObjectStore\StorageObjectStore;
use OC\Files\Storage\Temporary;
use OCP\Files\ObjectStore\IObjectStore;

class LocalTest extends ObjectStoreTestCase {
	/**
	 * @return IObjectStore
	 */
	protected function getInstance() {
		$storage = new Temporary();
		return new StorageObjectStore($storage);
	}
}
