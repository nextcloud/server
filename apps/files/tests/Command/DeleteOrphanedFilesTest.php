<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files\Tests\Command;

use OC\Files\View;
use OCA\Files\Command\DeleteOrphanedFiles;
use OCP\Files\StorageNotAvailableException;
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

	/**
	 * @var DeleteOrphanedFiles
	 */
	private $command;

	/**
	 * @var \OCP\IDBConnection
	 */
	private $connection;

	/**
	 * @var string
	 */
	private $user1;

	protected function setUp(): void {
		parent::setUp();

		$this->connection = \OC::$server->getDatabaseConnection();

		$this->user1 = $this->getUniqueID('user1_');

		$userManager = \OC::$server->getUserManager();
		$userManager->createUser($this->user1, 'pass');

		$this->command = new DeleteOrphanedFiles($this->connection);
	}

	protected function tearDown(): void {
		$userManager = \OC::$server->getUserManager();
		$user1 = $userManager->get($this->user1);
		if ($user1) {
			$user1->delete();
		}

		$this->logout();

		parent::tearDown();
	}

	protected function getFile($fileId) {
		$stmt = $this->connection->executeQuery('SELECT * FROM `*PREFIX*filecache` WHERE `fileid` = ?', [$fileId]);
		return $stmt->fetchAll();
	}

	protected function getMounts($storageId) {
		$stmt = $this->connection->executeQuery('SELECT * FROM `*PREFIX*mounts` WHERE `storage_id` = ?', [$storageId]);
		return $stmt->fetchAll();
	}

	/**
	 * Test clearing orphaned files
	 */
	public function testClearFiles() {
		$input = $this->getMockBuilder(InputInterface::class)
			->disableOriginalConstructor()
			->getMock();
		$output = $this->getMockBuilder(OutputInterface::class)
			->disableOriginalConstructor()
			->getMock();

		// scan home storage so that mounts are properly setup
		\OC::$server->getRootFolder()->getUserFolder($this->user1)->getStorage()->getScanner()->scan('');

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
		$output
			->expects($this->exactly(3))
			->method('writeln')
			->withConsecutive(
				['3 orphaned file cache entries deleted'],
				['0 orphaned file cache extended entries deleted'],
				['1 orphaned mount entries deleted'],
			);

		$this->command->execute($input, $output);

		$this->assertCount(0, $this->getFile($fileInfo->getId()), 'Asserts that file gets cleaned up');
		$this->assertCount(0, $this->getMounts($numericStorageId), 'Asserts that mount gets cleaned up');

		// since we deleted the storage it might throw a (valid) StorageNotAvailableException
		try {
			$view->unlink('files/test');
		} catch (StorageNotAvailableException $e) {
		}
	}
}
