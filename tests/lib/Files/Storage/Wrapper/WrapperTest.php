<?php
/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Files\Storage\Wrapper;

use OC\Files\Storage\Local;
use OC\Files\Storage\Wrapper\Wrapper;
use OCP\Files;
use OCP\ITempManager;
use OCP\Server;

class WrapperTest extends \Test\Files\Storage\Storage {
	/**
	 * @var string tmpDir
	 */
	private $tmpDir;

	protected function setUp(): void {
		parent::setUp();

		$this->tmpDir = Server::get(ITempManager::class)->getTemporaryFolder();
		$storage = new Local(['datadir' => $this->tmpDir]);
		$this->instance = new Wrapper(['storage' => $storage]);
	}

	protected function tearDown(): void {
		Files::rmdirr($this->tmpDir);
		parent::tearDown();
	}

	public function testInstanceOfStorageWrapper(): void {
		$this->assertTrue($this->instance->instanceOfStorage('\OC\Files\Storage\Local'));
		$this->assertTrue($this->instance->instanceOfStorage('\OC\Files\Storage\Wrapper\Wrapper'));
	}
}
