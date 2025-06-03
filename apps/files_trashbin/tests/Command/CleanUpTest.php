<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_Trashbin\Tests\Command;

use OCA\Files_Trashbin\Command\CleanUp;
use OCP\Files\IRootFolder;
use OCP\IDBConnection;
use OCP\IUserManager;
use OCP\Server;
use OCP\UserInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Test\TestCase;

/**
 * Class CleanUpTest
 *
 * @group DB
 *
 * @package OCA\Files_Trashbin\Tests\Command
 */
class CleanUpTest extends TestCase {
	protected IUserManager&MockObject $userManager;
	protected IRootFolder&MockObject $rootFolder;
	protected IDBConnection $dbConnection;
	protected CleanUp $cleanup;
	protected string $trashTable = 'files_trash';
	protected string $user0 = 'user0';

	protected function setUp(): void {
		parent::setUp();
		$this->rootFolder = $this->createMock(IRootFolder::class);
		$this->userManager = $this->createMock(IUserManager::class);

		$this->dbConnection = Server::get(IDBConnection::class);

		$this->cleanup = new CleanUp($this->rootFolder, $this->userManager, $this->dbConnection);
	}

	/**
	 * populate files_trash table with 10 dummy values
	 */
	public function initTable(): void {
		$query = $this->dbConnection->getQueryBuilder();
		$query->delete($this->trashTable)->executeStatement();
		for ($i = 0; $i < 10; $i++) {
			$query->insert($this->trashTable)
				->values([
					'id' => $query->expr()->literal('file' . $i),
					'timestamp' => $query->expr()->literal($i),
					'location' => $query->expr()->literal('.'),
					'user' => $query->expr()->literal('user' . $i % 2)
				])->executeStatement();
		}
		$getAllQuery = $this->dbConnection->getQueryBuilder();
		$result = $getAllQuery->select('id')
			->from($this->trashTable)
			->executeQuery()
			->fetchAll();
		$this->assertCount(10, $result);
	}

	/**
	 * @dataProvider dataTestRemoveDeletedFiles
	 */
	public function testRemoveDeletedFiles(bool $nodeExists): void {
		$this->initTable();
		$this->rootFolder
			->method('nodeExists')
			->with('/' . $this->user0 . '/files_trashbin')
			->willReturnOnConsecutiveCalls($nodeExists, false);
		if ($nodeExists) {
			$this->rootFolder
				->method('get')
				->with('/' . $this->user0 . '/files_trashbin')
				->willReturn($this->rootFolder);
			$this->rootFolder
				->method('delete');
		} else {
			$this->rootFolder->expects($this->never())->method('get');
			$this->rootFolder->expects($this->never())->method('delete');
		}
		self::invokePrivate($this->cleanup, 'removeDeletedFiles', [$this->user0, new NullOutput(), false]);

		if ($nodeExists) {
			// if the delete operation was executed only files from user1
			// should be left.
			$query = $this->dbConnection->getQueryBuilder();
			$query->select('user')
				->from($this->trashTable);

			$qResult = $query->executeQuery();
			$result = $qResult->fetchAll();
			$qResult->closeCursor();

			$this->assertCount(5, $result);
			foreach ($result as $r) {
				$this->assertSame('user1', $r['user']);
			}
		} else {
			// if no delete operation was executed we should still have all 10
			// database entries
			$getAllQuery = $this->dbConnection->getQueryBuilder();
			$result = $getAllQuery->select('id')
				->from($this->trashTable)
				->executeQuery()
				->fetchAll();
			$this->assertCount(10, $result);
		}
	}
	public static function dataTestRemoveDeletedFiles(): array {
		return [
			[true],
			[false]
		];
	}

	/**
	 * test remove deleted files from users given as parameter
	 */
	public function testExecuteDeleteListOfUsers(): void {
		$userIds = ['user1', 'user2', 'user3'];
		$instance = $this->getMockBuilder(\OCA\Files_Trashbin\Command\CleanUp::class)
			->onlyMethods(['removeDeletedFiles'])
			->setConstructorArgs([$this->rootFolder, $this->userManager, $this->dbConnection])
			->getMock();
		$instance->expects($this->exactly(count($userIds)))
			->method('removeDeletedFiles')
			->willReturnCallback(function ($user) use ($userIds): void {
				$this->assertTrue(in_array($user, $userIds));
			});
		$this->userManager->expects($this->exactly(count($userIds)))
			->method('userExists')->willReturn(true);
		$inputInterface = $this->createMock(\Symfony\Component\Console\Input\InputInterface::class);
		$inputInterface->method('getArgument')
			->with('user_id')
			->willReturn($userIds);
		$inputInterface->method('getOption')
			->willReturnMap([
				['all-users', false],
				['verbose', false],
			]);
		$outputInterface = $this->createMock(\Symfony\Component\Console\Output\OutputInterface::class);
		self::invokePrivate($instance, 'execute', [$inputInterface, $outputInterface]);
	}

	/**
	 * test remove deleted files of all users
	 */
	public function testExecuteAllUsers(): void {
		$userIds = [];
		$backendUsers = ['user1', 'user2'];
		$instance = $this->getMockBuilder(\OCA\Files_Trashbin\Command\CleanUp::class)
			->onlyMethods(['removeDeletedFiles'])
			->setConstructorArgs([$this->rootFolder, $this->userManager, $this->dbConnection])
			->getMock();
		$backend = $this->createMock(UserInterface::class);
		$backend->method('getUsers')
			->with('', 500, 0)
			->willReturn($backendUsers);
		$instance->expects($this->exactly(count($backendUsers)))
			->method('removeDeletedFiles')
			->willReturnCallback(function ($user) use ($backendUsers): void {
				$this->assertTrue(in_array($user, $backendUsers));
			});
		$inputInterface = $this->createMock(InputInterface::class);
		$inputInterface->method('getArgument')
			->with('user_id')
			->willReturn($userIds);
		$inputInterface->method('getOption')
			->willReturnMap([
				['all-users', true],
				['verbose', false],
			]);
		$outputInterface = $this->createMock(OutputInterface::class);
		$this->userManager
			->method('getBackends')
			->willReturn([$backend]);
		self::invokePrivate($instance, 'execute', [$inputInterface, $outputInterface]);
	}

	public function testExecuteNoUsersAndNoAllUsers(): void {
		$inputInterface = $this->createMock(InputInterface::class);
		$inputInterface->method('getArgument')
			->with('user_id')
			->willReturn([]);
		$inputInterface->method('getOption')
			->willReturnMap([
				['all-users', false],
				['verbose', false],
			]);
		$outputInterface = $this->createMock(OutputInterface::class);

		$this->expectException(InvalidOptionException::class);
		$this->expectExceptionMessage('Either specify a user_id or --all-users');

		self::invokePrivate($this->cleanup, 'execute', [$inputInterface, $outputInterface]);
	}

	public function testExecuteUsersAndAllUsers(): void {
		$inputInterface = $this->createMock(InputInterface::class);
		$inputInterface->method('getArgument')
			->with('user_id')
			->willReturn(['user1', 'user2']);
		$inputInterface->method('getOption')
			->willReturnMap([
				['all-users', true],
				['verbose', false],
			]);
		$outputInterface = $this->createMock(OutputInterface::class);

		$this->expectException(InvalidOptionException::class);
		$this->expectExceptionMessage('Either specify a user_id or --all-users');

		self::invokePrivate($this->cleanup, 'execute', [$inputInterface, $outputInterface]);
	}
}
