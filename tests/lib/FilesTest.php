<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test;

use OCP\Files;
use OCP\ITempManager;
use OCP\Server;

class FilesTest extends TestCase {

	/**
	 * Tests recursive folder deletion with rmdirr()
	 */
	public function testRecursiveFolderDeletion(): void {
		$baseDir = Server::get(ITempManager::class)->getTemporaryFolder() . '/';
		mkdir($baseDir . 'a/b/c/d/e', 0777, true);
		mkdir($baseDir . 'a/b/c1/d/e', 0777, true);
		mkdir($baseDir . 'a/b/c2/d/e', 0777, true);
		mkdir($baseDir . 'a/b1/c1/d/e', 0777, true);
		mkdir($baseDir . 'a/b2/c1/d/e', 0777, true);
		mkdir($baseDir . 'a/b3/c1/d/e', 0777, true);
		mkdir($baseDir . 'a1/b', 0777, true);
		mkdir($baseDir . 'a1/c', 0777, true);
		file_put_contents($baseDir . 'a/test.txt', 'Hello file!');
		file_put_contents($baseDir . 'a/b1/c1/test one.txt', 'Hello file one!');
		file_put_contents($baseDir . 'a1/b/test two.txt', 'Hello file two!');
		Files::rmdirr($baseDir . 'a');

		$this->assertFalse(file_exists($baseDir . 'a'));
		$this->assertTrue(file_exists($baseDir . 'a1'));

		Files::rmdirr($baseDir);
		$this->assertFalse(file_exists($baseDir));
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('streamCopyDataProvider')]
	public function testStreamCopy($expectedCount, $expectedResult, $source, $target): void {
		if (is_string($source)) {
			$source = fopen($source, 'r');
		}
		if (is_string($target)) {
			$target = fopen($target, 'w');
		}

		[$count, $result] = Files::streamCopy($source, $target, true);

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
