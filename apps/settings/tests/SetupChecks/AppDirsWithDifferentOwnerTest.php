<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
	public function testAppDirectoryOwnersOk(): void {
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
	public function testAppDirectoryOwnersNotWritable(): void {
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
