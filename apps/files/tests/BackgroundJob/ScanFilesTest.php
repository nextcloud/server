<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files\Tests\BackgroundJob;

use OC\Files\Mount\MountPoint;
use OC\Files\Storage\Temporary;
use OCA\Files\BackgroundJob\ScanFiles;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\Config\IUserMountCache;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IUser;
use OCP\Server;
use Psr\Log\LoggerInterface;
use Test\TestCase;
use Test\Traits\MountProviderTrait;
use Test\Traits\UserTrait;

/**
 * Class ScanFilesTest
 *
 * @package OCA\Files\Tests\BackgroundJob
 * @group DB
 */
class ScanFilesTest extends TestCase {
	use UserTrait;
	use MountProviderTrait;

	private ScanFiles $scanFiles;
	private IUserMountCache $mountCache;

	protected function setUp(): void {
		parent::setUp();

		$config = $this->createMock(IConfig::class);
		$dispatcher = $this->createMock(IEventDispatcher::class);
		$logger = $this->createMock(LoggerInterface::class);
		$connection = Server::get(IDBConnection::class);
		$this->mountCache = Server::get(IUserMountCache::class);

		$this->scanFiles = $this->getMockBuilder(ScanFiles::class)
			->setConstructorArgs([
				$config,
				$dispatcher,
				$logger,
				$connection,
				$this->createMock(ITimeFactory::class)
			])
			->onlyMethods(['runScanner'])
			->getMock();
	}

	private function runJob(): void {
		self::invokePrivate($this->scanFiles, 'run', [[]]);
	}

	private function getUser(string $userId): IUser {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')
			->willReturn($userId);
		return $user;
	}

	private function setupStorage(string $user, string $mountPoint) {
		$storage = new Temporary([]);
		$storage->mkdir('foo');
		$storage->getScanner()->scan('');

		$this->createUser($user, '');
		$this->mountCache->registerMounts($this->getUser($user), [
			new MountPoint($storage, $mountPoint)
		]);

		return $storage;
	}

	public function testAllScanned(): void {
		$this->setupStorage('foouser', '/foousers/files/foo');

		$this->scanFiles->expects($this->never())
			->method('runScanner');
		$this->runJob();
	}

	public function testUnscanned(): void {
		$storage = $this->setupStorage('foouser', '/foousers/files/foo');
		$storage->getCache()->put('foo', ['size' => -1]);

		$this->scanFiles->expects($this->once())
			->method('runScanner')
			->with('foouser');
		$this->runJob();
	}
}
