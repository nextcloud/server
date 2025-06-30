<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test;

use OC\Files\View;
use OC_Helper;

class LegacyHelperTest extends \Test\TestCase {
	/** @var string */
	private $originalWebRoot;

	protected function setUp(): void {
		$this->originalWebRoot = \OC::$WEBROOT;
	}

	protected function tearDown(): void {
		// Reset webRoot
		\OC::$WEBROOT = $this->originalWebRoot;
	}

	public function testBuildNotExistingFileNameForView(): void {
		$viewMock = $this->createMock(View::class);
		$this->assertEquals('/filename', OC_Helper::buildNotExistingFileNameForView('/', 'filename', $viewMock));
		$this->assertEquals('dir/filename.ext', OC_Helper::buildNotExistingFileNameForView('dir', 'filename.ext', $viewMock));

		$viewMock = $this->createMock(View::class);
		$viewMock->expects($this->exactly(2))
			->method('file_exists')
			->willReturnMap([
				// Conflict on filename.ext
				['dir/filename.ext', true],
				['dir/filename (2).ext', false],
			]);
		$this->assertEquals('dir/filename (2).ext', OC_Helper::buildNotExistingFileNameForView('dir', 'filename.ext', $viewMock));

		$viewMock = $this->createMock(View::class);
		$viewMock->expects($this->exactly(3))
			->method('file_exists')
			->willReturnMap([
				// Conflict on filename.ext
				['dir/filename.ext', true],
				['dir/filename (2).ext', true],
				['dir/filename (3).ext', false],
			]);
		$this->assertEquals('dir/filename (3).ext', OC_Helper::buildNotExistingFileNameForView('dir', 'filename.ext', $viewMock));

		$viewMock = $this->createMock(View::class);
		$viewMock->expects($this->exactly(2))
			->method('file_exists')
			->willReturnMap([
				['dir/filename (1).ext', true],
				['dir/filename (2).ext', false],
			]);
		$this->assertEquals('dir/filename (2).ext', OC_Helper::buildNotExistingFileNameForView('dir', 'filename (1).ext', $viewMock));

		$viewMock = $this->createMock(View::class);
		$viewMock->expects($this->exactly(2))
			->method('file_exists')
			->willReturnMap([
				['dir/filename (2).ext', true],
				['dir/filename (3).ext', false],
			]);
		$this->assertEquals('dir/filename (3).ext', OC_Helper::buildNotExistingFileNameForView('dir', 'filename (2).ext', $viewMock));

		$viewMock = $this->createMock(View::class);
		$viewMock->expects($this->exactly(3))
			->method('file_exists')
			->willReturnMap([
				['dir/filename (2).ext', true],
				['dir/filename (3).ext', true],
				['dir/filename (4).ext', false],
			]);
		$this->assertEquals('dir/filename (4).ext', OC_Helper::buildNotExistingFileNameForView('dir', 'filename (2).ext', $viewMock));

		$viewMock = $this->createMock(View::class);
		$viewMock->expects($this->exactly(2))
			->method('file_exists')
			->willReturnMap([
				['dir/filename(1).ext', true],
				['dir/filename(2).ext', false],
			]);
		$this->assertEquals('dir/filename(2).ext', OC_Helper::buildNotExistingFileNameForView('dir', 'filename(1).ext', $viewMock));

		$viewMock = $this->createMock(View::class);
		$viewMock->expects($this->exactly(2))
			->method('file_exists')
			->willReturnMap([
				['dir/filename(1) (1).ext', true],
				['dir/filename(1) (2).ext', false],
			]);
		$this->assertEquals('dir/filename(1) (2).ext', OC_Helper::buildNotExistingFileNameForView('dir', 'filename(1) (1).ext', $viewMock));

		$viewMock = $this->createMock(View::class);
		$viewMock->expects($this->exactly(3))
			->method('file_exists')
			->willReturnMap([
				['dir/filename(1) (1).ext', true],
				['dir/filename(1) (2).ext', true],
				['dir/filename(1) (3).ext', false],
			]);
		$this->assertEquals('dir/filename(1) (3).ext', OC_Helper::buildNotExistingFileNameForView('dir', 'filename(1) (1).ext', $viewMock));

		$viewMock = $this->createMock(View::class);
		$viewMock->expects($this->exactly(2))
			->method('file_exists')
			->willReturnMap([
				['dir/filename(1) (2) (3).ext', true],
				['dir/filename(1) (2) (4).ext', false],
			]);
		$this->assertEquals('dir/filename(1) (2) (4).ext', OC_Helper::buildNotExistingFileNameForView('dir', 'filename(1) (2) (3).ext', $viewMock));
	}

	/**
	 * @dataProvider streamCopyDataProvider
	 */
	public function testStreamCopy($expectedCount, $expectedResult, $source, $target): void {
		if (is_string($source)) {
			$source = fopen($source, 'r');
		}
		if (is_string($target)) {
			$target = fopen($target, 'w');
		}

		[$count, $result] = \OC_Helper::streamCopy($source, $target);

		if (is_resource($source)) {
			fclose($source);
		}
		if (is_resource($target)) {
			fclose($target);
		}

		$this->assertSame($expectedCount, $count);
		$this->assertSame($expectedResult, $result);
	}


	public static function streamCopyDataProvider(): array {
		return [
			[0, false, false, false],
			[0, false, \OC::$SERVERROOT . '/tests/data/lorem.txt', false],
			[filesize(\OC::$SERVERROOT . '/tests/data/lorem.txt'), true, \OC::$SERVERROOT . '/tests/data/lorem.txt', \OC::$SERVERROOT . '/tests/data/lorem-copy.txt'],
			[3670, true, \OC::$SERVERROOT . '/tests/data/testimage.png', \OC::$SERVERROOT . '/tests/data/testimage-copy.png'],
		];
	}
}
