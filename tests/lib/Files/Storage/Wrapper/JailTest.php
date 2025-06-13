<?php
/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Files\Storage\Wrapper;

use OC\Files\Filesystem;
use OC\Files\Storage\Temporary;
use OC\Files\Storage\Wrapper\Jail;

class JailTest extends \Test\Files\Storage\Storage {
	/**
	 * @var \OC\Files\Storage\Temporary
	 */
	private $sourceStorage;

	protected function setUp(): void {
		parent::setUp();
		$this->sourceStorage = new Temporary([]);
		$this->sourceStorage->mkdir('foo');
		$this->instance = new Jail([
			'storage' => $this->sourceStorage,
			'root' => 'foo'
		]);
	}

	protected function tearDown(): void {
		// test that nothing outside our jail is touched
		$contents = [];
		$dh = $this->sourceStorage->opendir('');
		while (($file = readdir($dh)) !== false) {
			if (!Filesystem::isIgnoredDir($file)) {
				$contents[] = $file;
			}
		}
		$this->assertEquals(['foo'], $contents);
		$this->sourceStorage->cleanUp();
		parent::tearDown();
	}

	public function testMkDirRooted(): void {
		$this->instance->mkdir('bar');
		$this->assertTrue($this->sourceStorage->is_dir('foo/bar'));
	}

	public function testFilePutContentsRooted(): void {
		$this->instance->file_put_contents('bar', 'asd');
		$this->assertEquals('asd', $this->sourceStorage->file_get_contents('foo/bar'));
	}
}
