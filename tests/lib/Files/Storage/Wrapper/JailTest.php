<?php
/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Files\Storage\Wrapper;

class JailTest extends \Test\Files\Storage\Storage {
	/**
	 * @var \OC\Files\Storage\Temporary
	 */
	private $sourceStorage;

	protected function setUp(): void {
		parent::setUp();
		$this->sourceStorage = new \OC\Files\Storage\Temporary([]);
		$this->sourceStorage->mkdir('foo');
		$this->instance = new \OC\Files\Storage\Wrapper\Jail([
			'storage' => $this->sourceStorage,
			'root' => 'foo'
		]);
	}

	protected function tearDown(): void {
		// test that nothing outside our jail is touched
		$contents = [];
		$dh = $this->sourceStorage->opendir('');
		while ($file = readdir($dh)) {
			if (!\OC\Files\Filesystem::isIgnoredDir($file)) {
				$contents[] = $file;
			}
		}
		$this->assertEquals(['foo'], $contents);
		$this->sourceStorage->cleanUp();
		parent::tearDown();
	}

	public function testMkDirRooted() {
		$this->instance->mkdir('bar');
		$this->assertTrue($this->sourceStorage->is_dir('foo/bar'));
	}

	public function testFilePutContentsRooted() {
		$this->instance->file_put_contents('bar', 'asd');
		$this->assertEquals('asd', $this->sourceStorage->file_get_contents('foo/bar'));
	}
}
