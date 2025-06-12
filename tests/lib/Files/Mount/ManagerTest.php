<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Files\Mount;

use OC\Files\Mount\MountPoint;
use OC\Files\SetupManagerFactory;
use OC\Files\Storage\Temporary;

class LongId extends Temporary {
	public function getId(): string {
		return 'long:' . str_repeat('foo', 50) . parent::getId();
	}
}

class ManagerTest extends \Test\TestCase {
	/**
	 * @var \OC\Files\Mount\Manager
	 */
	private $manager;

	protected function setUp(): void {
		parent::setUp();
		$this->manager = new \OC\Files\Mount\Manager($this->createMock(SetupManagerFactory::class));
	}

	public function testFind(): void {
		$rootMount = new MountPoint(new Temporary([]), '/');
		$this->manager->addMount($rootMount);
		$this->assertEquals($rootMount, $this->manager->find('/'));
		$this->assertEquals($rootMount, $this->manager->find('/foo/bar'));

		$storage = new Temporary([]);
		$mount1 = new MountPoint($storage, '/foo');
		$this->manager->addMount($mount1);
		$this->assertEquals($rootMount, $this->manager->find('/'));
		$this->assertEquals($mount1, $this->manager->find('/foo/bar'));

		$this->assertEquals(1, count($this->manager->findIn('/')));
		$mount2 = new MountPoint(new Temporary([]), '/bar');
		$this->manager->addMount($mount2);
		$this->assertEquals(2, count($this->manager->findIn('/')));

		$id = $mount1->getStorageId();
		$this->assertEquals([$mount1], $this->manager->findByStorageId($id));

		$mount3 = new MountPoint($storage, '/foo/bar');
		$this->manager->addMount($mount3);
		$this->assertEquals([$mount1, $mount3], $this->manager->findByStorageId($id));
	}

	public function testLong(): void {
		$storage = new LongId([]);
		$mount = new MountPoint($storage, '/foo');
		$this->manager->addMount($mount);

		$id = $mount->getStorageId();
		$storageId = $storage->getId();
		$this->assertEquals([$mount], $this->manager->findByStorageId($id));
		$this->assertEquals([$mount], $this->manager->findByStorageId($storageId));
		$this->assertEquals([$mount], $this->manager->findByStorageId(md5($storageId)));
	}
}
