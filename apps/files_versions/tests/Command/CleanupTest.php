<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_Versions\Tests\Command;

use OC\User\Manager;
use OCA\Files_Versions\Command\CleanUp;
use OCA\Files_Versions\Db\VersionsMapper;
use OCP\Files\Cache\ICache;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\Storage\IStorage;
use OCP\UserInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

/**
 * Class CleanupTest
 *
 *
 * @package OCA\Files_Versions\Tests\Command
 */
#[\PHPUnit\Framework\Attributes\Group(name: 'DB')]
class CleanupTest extends TestCase {
	protected Manager&MockObject $userManager;
	protected IRootFolder&MockObject $rootFolder;
	protected VersionsMapper&MockObject $versionMapper;
	protected CleanUp $cleanup;

	protected function setUp(): void {
		parent::setUp();

		$this->rootFolder = $this->createMock(IRootFolder::class);
		$this->userManager = $this->createMock(Manager::class);
		$this->versionMapper = $this->createMock(VersionsMapper::class);

		$this->cleanup = new CleanUp($this->rootFolder, $this->userManager, $this->versionMapper);
	}

	/**
	 * @param boolean $nodeExists
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'dataTestDeleteVersions')]
	public function testDeleteVersions(bool $nodeExists): void {
		$this->rootFolder->expects($this->once())
			->method('nodeExists')
			->with('/testUser/files_versions')
			->willReturn($nodeExists);

		$userFolder = $this->createMock(Folder::class);
		$userHomeStorage = $this->createMock(IStorage::class);
		$userHomeStorageCache = $this->createMock(ICache::class);
		$this->rootFolder->expects($this->once())
			->method('getUserFolder')
			->willReturn($userFolder);
		$userFolder->expects($this->once())
			->method('getStorage')
			->willReturn($userHomeStorage);
		$userHomeStorage->expects($this->once())
			->method('getCache')
			->willReturn($userHomeStorageCache);
		$userHomeStorageCache->expects($this->once())
			->method('getNumericStorageId')
			->willReturn(1);

		if ($nodeExists) {
			$this->rootFolder->expects($this->once())
				->method('get')
				->with('/testUser/files_versions')
				->willReturn($this->rootFolder);
			$this->rootFolder->expects($this->once())
				->method('delete');
		} else {
			$this->rootFolder->expects($this->never())
				->method('get');
			$this->rootFolder->expects($this->never())
				->method('delete');
		}

		$this->invokePrivate($this->cleanup, 'deleteVersions', ['testUser']);
	}

	public static function dataTestDeleteVersions(): array {
		return [
			[true],
			[false]
		];
	}


	/**
	 * test delete versions from users given as parameter
	 */
	public function testExecuteDeleteListOfUsers(): void {
		$userIds = ['user1', 'user2', 'user3'];

		$instance = $this->getMockBuilder(CleanUp::class)
			->onlyMethods(['deleteVersions'])
			->setConstructorArgs([$this->rootFolder, $this->userManager, $this->versionMapper])
			->getMock();
		$instance->expects($this->exactly(count($userIds)))
			->method('deleteVersions')
			->willReturnCallback(function ($user) use ($userIds): void {
				$this->assertTrue(in_array($user, $userIds));
			});

		$this->userManager->expects($this->exactly(count($userIds)))
			->method('userExists')->willReturn(true);

		$inputInterface = $this->createMock(\Symfony\Component\Console\Input\InputInterface::class);
		$inputInterface->expects($this->once())->method('getArgument')
			->with('user_id')
			->willReturn($userIds);

		$outputInterface = $this->createMock(\Symfony\Component\Console\Output\OutputInterface::class);

		$this->invokePrivate($instance, 'execute', [$inputInterface, $outputInterface]);
	}

	/**
	 * test delete versions of all users
	 */
	public function testExecuteAllUsers(): void {
		$userIds = [];
		$backendUsers = ['user1', 'user2'];

		$instance = $this->getMockBuilder(CleanUp::class)
			->onlyMethods(['deleteVersions'])
			->setConstructorArgs([$this->rootFolder, $this->userManager, $this->versionMapper])
			->getMock();

		$backend = $this->getMockBuilder(UserInterface::class)
			->disableOriginalConstructor()->getMock();
		$backend->expects($this->once())->method('getUsers')
			->with('', 500, 0)
			->willReturn($backendUsers);

		$instance->expects($this->exactly(count($backendUsers)))
			->method('deleteVersions')
			->willReturnCallback(function ($user) use ($backendUsers): void {
				$this->assertTrue(in_array($user, $backendUsers));
			});

		$inputInterface = $this->createMock(\Symfony\Component\Console\Input\InputInterface::class);
		$inputInterface->expects($this->once())->method('getArgument')
			->with('user_id')
			->willReturn($userIds);

		$outputInterface = $this->createMock(\Symfony\Component\Console\Output\OutputInterface::class);

		$this->userManager->expects($this->once())
			->method('getBackends')
			->willReturn([$backend]);

		$this->invokePrivate($instance, 'execute', [$inputInterface, $outputInterface]);
	}
}
