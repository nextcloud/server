<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Files\Storage;

use Test\TestCase;

abstract class StoragesTest extends TestCase {
	/**
	 * @var \OC\Files\Storage\Storage
	 */
	protected $storage1;

	/**
	 * @var \OC\Files\Storage\Storage
	 */
	protected $storage2;

	protected function tearDown(): void {
		if (is_null($this->storage1) && is_null($this->storage2)) {
			return;
		}
		$this->storage1->getCache()->clear();
		$this->storage2->getCache()->clear();

		parent::tearDown();
	}

	public function testMoveFileFromStorage() {
		$source = 'source.txt';
		$target = 'target.txt';
		$storage2->file_put_contents($source, 'foo');

		$storage1->moveFromStorage($storage2, $source, $target);

		$this->assertTrue($storage1->file_exists($target), $target.' was not created');
		$this->assertFalse($storage2->file_exists($source), $source.' still exists');
		$this->assertEquals('foo', $storage1->file_get_contents($target));
	}

	public function testMoveDirectoryFromStorage() {
		$storage2->mkdir('source');
		$storage2->file_put_contents('source/test1.txt', 'foo');
		$storage2->file_put_contents('source/test2.txt', 'qwerty');
		$storage2->mkdir('source/subfolder');
		$storage2->file_put_contents('source/subfolder/test.txt', 'bar');

		$storage1->moveFromStorage($storage2, 'source', 'target');

		$this->assertTrue($storage1->file_exists('target'));
		$this->assertTrue($storage1->file_exists('target/test1.txt'));
		$this->assertTrue($storage1->file_exists('target/test2.txt'));
		$this->assertTrue($storage1->file_exists('target/subfolder'));
		$this->assertTrue($storage1->file_exists('target/subfolder/test.txt'));

		$this->assertFalse($storage2->file_exists('source'));
		$this->assertFalse($storage2->file_exists('source/test1.txt'));
		$this->assertFalse($storage2->file_exists('source/test2.txt'));
		$this->assertFalse($storage2->file_exists('source/subfolder'));
		$this->assertFalse($storage2->file_exists('source/subfolder/test.txt'));

		$this->assertEquals('foo', $storage1->file_get_contents('target/test1.txt'));
		$this->assertEquals('qwerty', $storage1->file_get_contents('target/test2.txt'));
		$this->assertEquals('bar', $storage1->file_get_contents('target/subfolder/test.txt'));
	}

	public function testCopyFileFromStorage() {
		$source = 'source.txt';
		$target = 'target.txt';
		$storage2->file_put_contents($source, 'foo');

		$storage1->copyFromStorage($storage2, $source, $target);

		$this->assertTrue($storage1->file_exists($target), $target.' was not created');
		$this->assertTrue($storage2->file_exists($source), $source.' was deleted');
		$this->assertEquals('foo', $storage1->file_get_contents($target));
	}

	public function testCopyDirectoryFromStorage() {
		$storage2->mkdir('source');
		$storage2->file_put_contents('source/test1.txt', 'foo');
		$storage2->file_put_contents('source/test2.txt', 'qwerty');
		$storage2->mkdir('source/subfolder');
		$storage2->file_put_contents('source/subfolder/test.txt', 'bar');

		$storage1->copyFromStorage($storage2, 'source', 'target');

		$this->assertTrue($storage1->file_exists('target'));
		$this->assertTrue($storage1->file_exists('target/test1.txt'));
		$this->assertTrue($storage1->file_exists('target/test2.txt'));
		$this->assertTrue($storage1->file_exists('target/subfolder'));
		$this->assertTrue($storage1->file_exists('target/subfolder/test.txt'));

		$this->assertTrue($storage2->file_exists('source'));
		$this->assertTrue($storage2->file_exists('source/test1.txt'));
		$this->assertTrue($storage2->file_exists('source/test2.txt'));
		$this->assertTrue($storage2->file_exists('source/subfolder'));
		$this->assertTrue($storage2->file_exists('source/subfolder/test.txt'));

		$this->assertEquals('foo', $storage1->file_get_contents('target/test1.txt'));
		$this->assertEquals('qwerty', $storage1->file_get_contents('target/test2.txt'));
		$this->assertEquals('bar', $storage1->file_get_contents('target/subfolder/test.txt'));
	}
}
