<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Files\ObjectStore;

use OC\Files\ObjectStore\StorageObjectStore;
use OC\Files\Storage\Temporary;
use OCP\Files\ObjectStore\IObjectStore;
use Test\Files\Storage\StoragesTestCase;

/**
 * @group DB
 */
class ObjectStoreStoragesDifferentBucketTest extends StoragesTestCase {
	/**
	 * @var IObjectStore
	 */
	private $objectStore1;

	/**
	 * @var IObjectStore
	 */
	private $objectStore2;

	protected function setUp(): void {
		parent::setUp();

		$baseStorage1 = new Temporary();
		$this->objectStore1 = new StorageObjectStore($baseStorage1);
		$config['objectstore'] = $this->objectStore1;
		$this->storage1 = new ObjectStoreStorageOverwrite($config);

		$baseStorage2 = new Temporary();
		$this->objectStore2 = new StorageObjectStore($baseStorage2);
		$config['objectstore'] = $this->objectStore2;
		$this->storage2 = new ObjectStoreStorageOverwrite($config);
	}
}
