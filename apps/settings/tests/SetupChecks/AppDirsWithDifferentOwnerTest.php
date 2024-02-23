<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2024 Côme Chilliet <come.chilliet@nextcloud.com>
 *
 * @author Côme Chilliet <come.chilliet@nextcloud.com>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCA\Settings\Tests;

use OCA\Settings\SetupChecks\AppDirsWithDifferentOwner;
use OCP\IL10N;
use Test\TestCase;

class AppDirsWithDifferentOwnerTest extends TestCase {
	private IL10N $l10n;
	private AppDirsWithDifferentOwner $check;

	/**
	 * Holds a list of directories created during tests.
	 *
	 * @var array
	 */
	private $dirsToRemove = [];

	protected function setUp(): void {
		parent::setUp();

		$this->l10n = $this->getMockBuilder(IL10N::class)
			->disableOriginalConstructor()->getMock();
		$this->l10n->expects($this->any())
			->method('t')
			->willReturnCallback(function ($message, array $replace) {
				return vsprintf($message, $replace);
			});
		$this->check = new AppDirsWithDifferentOwner(
			$this->l10n,
		);
	}

	/**
	 * Setups a temp directory and some subdirectories.
	 * Then calls the 'getAppDirsWithDifferentOwner' method.
	 * The result is expected to be empty since
	 * there are no directories with different owners than the current user.
	 *
	 * @return void
	 */
	public function testAppDirectoryOwnersOk() {
		$tempDir = tempnam(sys_get_temp_dir(), 'apps') . 'dir';
		mkdir($tempDir);
		mkdir($tempDir . DIRECTORY_SEPARATOR . 'app1');
		mkdir($tempDir . DIRECTORY_SEPARATOR . 'app2');
		$this->dirsToRemove[] = $tempDir . DIRECTORY_SEPARATOR . 'app1';
		$this->dirsToRemove[] = $tempDir . DIRECTORY_SEPARATOR . 'app2';
		$this->dirsToRemove[] = $tempDir;
		\OC::$APPSROOTS = [
			[
				'path' => $tempDir,
				'url' => '/apps',
				'writable' => true,
			],
		];
		$this->assertSame(
			[],
			$this->invokePrivate($this->check, 'getAppDirsWithDifferentOwner', [posix_getuid()])
		);
	}

	/**
	 * Calls the check for a none existing app root that is marked as not writable.
	 * It's expected that no error happens since the check shouldn't apply.
	 *
	 * @return void
	 */
	public function testAppDirectoryOwnersNotWritable() {
		$tempDir = tempnam(sys_get_temp_dir(), 'apps') . 'dir';
		\OC::$APPSROOTS = [
			[
				'path' => $tempDir,
				'url' => '/apps',
				'writable' => false,
			],
		];
		$this->assertSame(
			[],
			$this->invokePrivate($this->check, 'getAppDirsWithDifferentOwner', [posix_getuid()])
		);
	}

	/**
	 * Removes directories created during tests.
	 *
	 * @after
	 * @return void
	 */
	public function removeTestDirectories() {
		foreach ($this->dirsToRemove as $dirToRemove) {
			rmdir($dirToRemove);
		}
		$this->dirsToRemove = [];
	}
}
