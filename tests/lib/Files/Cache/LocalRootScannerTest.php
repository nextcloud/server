<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2020 Robin Appelman <robin@icewind.nl>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
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
