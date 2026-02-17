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
}
