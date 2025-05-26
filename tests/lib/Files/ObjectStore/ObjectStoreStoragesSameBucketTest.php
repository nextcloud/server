<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Files\ObjectStore;

use OC\Files\ObjectStore\StorageObjectStore;
use OC\Files\Storage\Temporary;
use Test\Files\Storage\StoragesTest;

/**
 * @group DB
 */
class ObjectStoreStoragesSameBucketTest extends StoragesTest {
	/**
	 * @var \OCP\Files\ObjectStore\IObjectStore
	 */
	private $objectStore;

	protected function setUp(): void {
		parent::setUp();

		$baseStorage = new Temporary();
		$this->objectStore = new StorageObjectStore($baseStorage);
		$config['objectstore'] = $this->objectStore;
		// storage1 and storage2 share the same object store.
		$this->storage1 = new ObjectStoreStorageOverwrite($config);
		$this->storage2 = new ObjectStoreStorageOverwrite($config);
	}
}
