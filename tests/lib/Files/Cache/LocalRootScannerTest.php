<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Files\Cache;

use OC\Files\Storage\LocalRootStorage;
use Test\TestCase;

/**
 * @group DB
 */
class LocalRootScannerTest extends TestCase {
	/** @var LocalRootStorage */
	private $storage;

	protected function setUp(): void {
		parent::setUp();

		$folder = \OC::$server->getTempManager()->getTemporaryFolder();
		$this->storage = new LocalRootStorage(['datadir' => $folder]);
	}

	public function testDontScanUsers() {
		$this->storage->mkdir('foo');
		$this->storage->mkdir('foo/bar');

		$this->storage->getScanner()->scan('');
		$this->assertFalse($this->storage->getCache()->inCache('foo'));
	}

	public function testDoScanAppData() {
		$this->storage->mkdir('appdata_foo');
		$this->storage->mkdir('appdata_foo/bar');

		$this->storage->getScanner()->scan('');
		$this->assertTrue($this->storage->getCache()->inCache('appdata_foo'));
		$this->assertTrue($this->storage->getCache()->inCache('appdata_foo/bar'));
	}
}
