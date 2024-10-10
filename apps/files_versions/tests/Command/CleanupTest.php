<?php
/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_Versions\Tests\Command;

use OC\User\Manager;
use OCA\Files_Versions\Command\CleanUp;
use OCP\Files\Cache\ICache;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\Storage\IStorage;
use OCP\UserInterface;
use Test\TestCase;

/**
 * Class CleanupTest
 *
 * @group DB
 *
 * @package OCA\Files_Versions\Tests\Command
 */
class CleanupTest extends TestCase {

	/** @var CleanUp */
	protected $cleanup;

	/** @var \PHPUnit\Framework\MockObject\MockObject | Manager */
	protected $userManager;

	/** @var \PHPUnit\Framework\MockObject\MockObject | IRootFolder */
	protected $rootFolder;

	/** @var \PHPUnit\Framework\MockObject\MockObject | VersionsMapper */
	protected $versionMapper;

	protected function setUp(): void {
		parent::setUp();

		$this->rootFolder = $this->getMockBuilder('OCP\Files\IRootFolder')
			->disableOriginalConstructor()->getMock();
		$this->userManager = $this->getMockBuilder('OC\User\Manager')
			->disableOriginalConstructor()->getMock();
		$this->versionMapper = $this->getMockBuilder('OCA\Files_Versions\Db\VersionsMapper')
			->disableOriginalConstructor()->getMock();

		$this->cleanup = new CleanUp($this->rootFolder, $this->userManager, $this->versionMapper);
	}

	/**
	 * @dataProvider dataTestDeleteVersions
	 * @param boolean $nodeExists
	 */
	public function testDeleteVersions($nodeExists): void {
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

	public function dataTestDeleteVersions() {
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

		$instance = $this->getMockBuilder('OCA\Files_Versions\Command\CleanUp')
			->setMethods(['deleteVersions'])
			->setConstructorArgs([$this->rootFolder, $this->userManager, $this->versionMapper])
			->getMock();
		$instance->expects($this->exactly(count($userIds)))
			->method('deleteVersions')
			->willReturnCallback(function ($user) use ($userIds): void {
				$this->assertTrue(in_array($user, $userIds));
			});

		$this->userManager->expects($this->exactly(count($userIds)))
			->method('userExists')->willReturn(true);

		$inputInterface = $this->getMockBuilder('\Symfony\Component\Console\Input\InputInterface')
			->disableOriginalConstructor()->getMock();
		$inputInterface->expects($this->once())->method('getArgument')
			->with('user_id')
			->willReturn($userIds);

		$outputInterface = $this->getMockBuilder('\Symfony\Component\Console\Output\OutputInterface')
			->disableOriginalConstructor()->getMock();

		$this->invokePrivate($instance, 'execute', [$inputInterface, $outputInterface]);
	}

	/**
	 * test delete versions of all users
	 */
	public function testExecuteAllUsers(): void {
		$userIds = [];
		$backendUsers = ['user1', 'user2'];

		$instance = $this->getMockBuilder('OCA\Files_Versions\Command\CleanUp')
			->setMethods(['deleteVersions'])
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

		$inputInterface = $this->getMockBuilder('\Symfony\Component\Console\Input\InputInterface')
			->disableOriginalConstructor()->getMock();
		$inputInterface->expects($this->once())->method('getArgument')
			->with('user_id')
			->willReturn($userIds);

		$outputInterface = $this->getMockBuilder('\Symfony\Component\Console\Output\OutputInterface')
			->disableOriginalConstructor()->getMock();

		$this->userManager->expects($this->once())
			->method('getBackends')
			->willReturn([$backend]);

		$this->invokePrivate($instance, 'execute', [$inputInterface, $outputInterface]);
	}
}
