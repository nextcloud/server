<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files\Tests\Command;

use OC\Files\View;
use OCA\Files\Command\DeleteOrphanedFiles;
use OCP\Files\IRootFolder;
use OCP\Files\StorageNotAvailableException;
use OCP\IDBConnection;
use OCP\IUserManager;
use OCP\Server;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Test\TestCase;

/**
 * Class DeleteOrphanedFilesTest
 *
 * @group DB
 *
 * @package OCA\Files\Tests\Command
 */
class DeleteOrphanedFilesTest extends TestCase {

	private DeleteOrphanedFiles $command;
	private IDBConnection $connection;
	private string $user1;

	protected function setUp(): void {
		parent::setUp();

		$this->connection = Server::get(IDBConnection::class);

		$this->user1 = $this->getUniqueID('user1_');

		$userManager = Server::get(IUserManager::class);
		$userManager->createUser($this->user1, 'pass');

		$this->command = new DeleteOrphanedFiles($this->connection);
	}

	protected function tearDown(): void {
		$userManager = Server::get(IUserManager::class);
		$user1 = $userManager->get($this->user1);
		if ($user1) {
			$user1->delete();
		}

		$this->logout();

		parent::tearDown();
	}

	protected function getFile(int $fileId): array {
		$query = $this->connection->getQueryBuilder();
		$query->select('*')
			->from('filecache')
			->where($query->expr()->eq('fileid', $query->createNamedParameter($fileId)));
		return $query->executeQuery()->fetchAll();
	}

	protected function getMounts(int $storageId): array {
		$query = $this->connection->getQueryBuilder();
		$query->select('*')
			->from('mounts')
			->where($query->expr()->eq('storage_id', $query->createNamedParameter($storageId)));
		return $query->executeQuery()->fetchAll();
	}

	/**
	 * Test clearing orphaned files
	 */
	public function testClearFiles(): void {
		$input = $this->createMock(InputInterface::class);
		$output = $this->createMock(OutputInterface::class);

		$rootFolder = Server::get(IRootFolder::class);

		// scan home storage so that mounts are properly setup
		$rootFolder->getUserFolder($this->user1)->getStorage()->getScanner()->scan('');

		$this->loginAsUser($this->user1);

		$view = new View('/' . $this->user1 . '/');
		$view->mkdir('files/test');

		$fileInfo = $view->getFileInfo('files/test');

		$storageId = $fileInfo->getStorage()->getId();
		$numericStorageId = $fileInfo->getStorage()->getStorageCache()->getNumericId();

		$this->assertCount(1, $this->getFile($fileInfo->getId()), 'Asserts that file is available');
		$this->assertCount(1, $this->getMounts($numericStorageId), 'Asserts that mount is available');

		$this->command->execute($input, $output);

		$this->assertCount(1, $this->getFile($fileInfo->getId()), 'Asserts that file is still available');
		$this->assertCount(1, $this->getMounts($numericStorageId), 'Asserts that mount is still available');


		$deletedRows = $this->connection->executeUpdate('DELETE FROM `*PREFIX*storages` WHERE `id` = ?', [$storageId]);
		$this->assertNotNull($deletedRows, 'Asserts that storage got deleted');
		$this->assertSame(1, $deletedRows, 'Asserts that storage got deleted');

		// parent folder, `files`, ´test` and `welcome.txt` => 4 elements
		$calls = [
			'3 orphaned file cache entries deleted',
			'0 orphaned file cache extended entries deleted',
			'1 orphaned mount entries deleted',
		];
		$output
			->expects($this->exactly(3))
			->method('writeln')
			->willReturnCallback(function (string $message) use (&$calls) {
				$expected = array_shift($calls);
				$this->assertSame($expected, $message);
			});

		$this->command->execute($input, $output);

		$this->assertCount(0, $this->getFile($fileInfo->getId()), 'Asserts that file gets cleaned up');
		$this->assertCount(0, $this->getMounts($numericStorageId), 'Asserts that mount gets cleaned up');

		// Rescan folder to add back to cache before deleting
		$rootFolder->getUserFolder($this->user1)->getStorage()->getScanner()->scan('');
		// since we deleted the storage it might throw a (valid) StorageNotAvailableException
		try {
			$view->unlink('files/test');
		} catch (StorageNotAvailableException $e) {
		}
	}
}
