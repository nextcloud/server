<?php
/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud GmbH.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_Sharing\Tests\Command;

use OCA\Files_Sharing\Command\CleanupRemoteStorages;
use OCP\Federation\ICloudId;
use OCP\Federation\ICloudIdManager;
use OCP\IDBConnection;
use OCP\Server;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Test\TestCase;

/**
 * Class CleanupRemoteStoragesTest
 *
 * @group DB
 *
 * @package OCA\Files_Sharing\Tests\Command
 */
class CleanupRemoteStoragesTest extends TestCase {

	protected IDBConnection $connection;
	protected CleanupRemoteStorages $command;
	private ICloudIdManager&MockObject $cloudIdManager;

	private $storages = [
		['id' => 'shared::7b4a322b22f9d0047c38d77d471ce3cf', 'share_token' => 'f2c69dad1dc0649f26976fd210fc62e1', 'remote' => 'https://hostname.tld/owncloud1', 'user' => 'user1'],
		['id' => 'shared::efe3b456112c3780da6155d3a9b9141c', 'share_token' => 'f2c69dad1dc0649f26976fd210fc62e2', 'remote' => 'https://hostname.tld/owncloud2', 'user' => 'user2'],
		['notExistingId' => 'shared::33323d9f4ca416a9e3525b435354bc6f', 'share_token' => 'f2c69dad1dc0649f26976fd210fc62e3', 'remote' => 'https://hostname.tld/owncloud3', 'user' => 'user3'],
		['id' => 'shared::7fe41a07d3f517a923f4b2b599e72cbb', 'files_count' => 2],
		['id' => 'shared::de4aeb2f378d222b6d2c5fd8f4e42f8e', 'share_token' => 'f2c69dad1dc0649f26976fd210fc62e5', 'remote' => 'https://hostname.tld/owncloud5', 'user' => 'user5'],
		['id' => 'shared::af712293ab5eb9e6a1745a13818b99fe', 'files_count' => 3],
		['notExistingId' => 'shared::c34568c143cdac7d2f06e0800b5280f9', 'share_token' => 'f2c69dad1dc0649f26976fd210fc62e7', 'remote' => 'https://hostname.tld/owncloud7', 'user' => 'user7'],
	];

	protected function setUp(): void {
		parent::setUp();

		$this->connection = Server::get(IDBConnection::class);

		$storageQuery = Server::get(IDBConnection::class)->getQueryBuilder();
		$storageQuery->insert('storages')
			->setValue('id', $storageQuery->createParameter('id'));

		$shareExternalQuery = Server::get(IDBConnection::class)->getQueryBuilder();
		$shareExternalQuery->insert('share_external')
			->setValue('share_token', $shareExternalQuery->createParameter('share_token'))
			->setValue('remote', $shareExternalQuery->createParameter('remote'))
			->setValue('name', $shareExternalQuery->createParameter('name'))
			->setValue('owner', $shareExternalQuery->createParameter('owner'))
			->setValue('user', $shareExternalQuery->createParameter('user'))
			->setValue('mountpoint', $shareExternalQuery->createParameter('mountpoint'))
			->setValue('mountpoint_hash', $shareExternalQuery->createParameter('mountpoint_hash'));

		$filesQuery = Server::get(IDBConnection::class)->getQueryBuilder();
		$filesQuery->insert('filecache')
			->setValue('storage', $filesQuery->createParameter('storage'))
			->setValue('path', $filesQuery->createParameter('path'))
			->setValue('path_hash', $filesQuery->createParameter('path_hash'));

		foreach ($this->storages as &$storage) {
			if (isset($storage['id'])) {
				$storageQuery->setParameter('id', $storage['id']);
				$storageQuery->executeStatement();
				$storage['numeric_id'] = $storageQuery->getLastInsertId();
			}

			if (isset($storage['share_token'])) {
				$shareExternalQuery
					->setParameter('share_token', $storage['share_token'])
					->setParameter('remote', $storage['remote'])
					->setParameter('name', 'irrelevant')
					->setParameter('owner', 'irrelevant')
					->setParameter('user', $storage['user'])
					->setParameter('mountpoint', 'irrelevant')
					->setParameter('mountpoint_hash', 'irrelevant');
				$shareExternalQuery->executeStatement();
			}

			if (isset($storage['files_count'])) {
				for ($i = 0; $i < $storage['files_count']; $i++) {
					$filesQuery->setParameter('storage', $storage['numeric_id']);
					$filesQuery->setParameter('path', 'file' . $i);
					$filesQuery->setParameter('path_hash', md5('file' . $i));
					$filesQuery->executeStatement();
				}
			}
		}

		$this->cloudIdManager = $this->createMock(ICloudIdManager::class);

		$this->command = new CleanupRemoteStorages($this->connection, $this->cloudIdManager);
	}

	protected function tearDown(): void {
		$storageQuery = Server::get(IDBConnection::class)->getQueryBuilder();
		$storageQuery->delete('storages')
			->where($storageQuery->expr()->eq('id', $storageQuery->createParameter('id')));

		$shareExternalQuery = Server::get(IDBConnection::class)->getQueryBuilder();
		$shareExternalQuery->delete('share_external')
			->where($shareExternalQuery->expr()->eq('share_token', $shareExternalQuery->createParameter('share_token')))
			->andWhere($shareExternalQuery->expr()->eq('remote', $shareExternalQuery->createParameter('remote')));

		foreach ($this->storages as $storage) {
			if (isset($storage['id'])) {
				$storageQuery->setParameter('id', $storage['id']);
				$storageQuery->executeStatement();
			}

			if (isset($storage['share_token'])) {
				$shareExternalQuery->setParameter('share_token', $storage['share_token']);
				$shareExternalQuery->setParameter('remote', $storage['remote']);
				$shareExternalQuery->executeStatement();
			}
		}

		parent::tearDown();
	}

	private function doesStorageExist($numericId) {
		$qb = Server::get(IDBConnection::class)->getQueryBuilder();
		$qb->select('*')
			->from('storages')
			->where($qb->expr()->eq('numeric_id', $qb->createNamedParameter($numericId)));

		$qResult = $qb->executeQuery();
		$result = $qResult->fetch();
		$qResult->closeCursor();
		if (!empty($result)) {
			return true;
		}

		$qb = Server::get(IDBConnection::class)->getQueryBuilder();
		$qb->select('*')
			->from('filecache')
			->where($qb->expr()->eq('storage', $qb->createNamedParameter($numericId)));

		$qResult = $qb->executeQuery();
		$result = $qResult->fetch();
		$qResult->closeCursor();
		if (!empty($result)) {
			return true;
		}

		return false;
	}

	/**
	 * Test cleanup of orphaned storages
	 */
	public function testCleanup(): void {
		$input = $this->getMockBuilder(InputInterface::class)
			->disableOriginalConstructor()
			->getMock();
		$output = $this->getMockBuilder(OutputInterface::class)
			->disableOriginalConstructor()
			->getMock();

		// parent folder, `files`, ´test` and `welcome.txt` => 4 elements
		$outputCalls = [];
		$output
			->expects($this->any())
			->method('writeln')
			->willReturnCallback(function (string $text) use (&$outputCalls) {
				$outputCalls[] = $text;
			});

		$this->cloudIdManager
			->expects($this->any())
			->method('getCloudId')
			->will($this->returnCallback(function (string $user, string $remote) {
				$cloudIdMock = $this->createMock(ICloudId::class);

				// The remotes are already sanitized in the original data, so
				// they can be directly returned.
				$cloudIdMock
					->expects($this->any())
					->method('getRemote')
					->willReturn($remote);

				return $cloudIdMock;
			}));

		$this->command->execute($input, $output);

		$this->assertTrue($this->doesStorageExist($this->storages[0]['numeric_id']));
		$this->assertTrue($this->doesStorageExist($this->storages[1]['numeric_id']));
		$this->assertFalse($this->doesStorageExist($this->storages[3]['numeric_id']));
		$this->assertTrue($this->doesStorageExist($this->storages[4]['numeric_id']));
		$this->assertFalse($this->doesStorageExist($this->storages[5]['numeric_id']));

		$this->assertEquals([
			'5 remote storage(s) need(s) to be checked',
			'5 remote share(s) exist',
		], array_slice($outputCalls, 0, 2));
	}
}
