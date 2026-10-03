<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Files\Tests\BackgroundJob;

use OC\Files\Mount\MountPoint;
use OC\Files\SetupManager;
use OC\Files\Storage\Temporary;
use OCA\Files\BackgroundJob\ScanFiles;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\DB\IResult;
use OCP\DB\QueryBuilder\IExpressionBuilder;
use OCP\DB\QueryBuilder\IParameter;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OC\DB\QueryBuilder\Sharded\ShardDefinition;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\Config\IUserMountCache;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Server;
use Psr\Log\LoggerInterface;
use Test\TestCase;
use Test\Traits\MountProviderTrait;
use Test\Traits\UserTrait;

/**
 * Class ScanFilesTest
 *
 * @package OCA\Files\Tests\BackgroundJob
 */
#[\PHPUnit\Framework\Attributes\Group(name: 'DB')]
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
				$this->createMock(ITimeFactory::class),
				$this->createMock(SetupManager::class),
				$this->createMock(IUserManager::class),
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

	public function testShardedQueryFindsUserWithoutFallback(): void {
		$connection = $this->createMock(IDBConnection::class);
		$connection->method('getShardDefinition')
			->with('filecache')
			->willReturn($this->createMock(\OC\DB\QueryBuilder\Sharded\ShardDefinition::class));

		$query = $this->createMock(IQueryBuilder::class);
		$expressionBuilder = $this->createMock(IExpressionBuilder::class);
		$result = $this->createMock(IResult::class);

		$connection->expects($this->once())
			->method('getQueryBuilder')
			->willReturn($query);

		$query->method('expr')->willReturn($expressionBuilder);
		$expressionBuilder->method('eq')->willReturn('condition');
		$expressionBuilder->method('gt')->willReturn('condition');

		$query->method('select')->willReturnSelf();
		$query->method('from')->willReturnSelf();
		$query->method('leftJoin')->willReturnSelf();
		$query->method('where')->willReturnSelf();
		$query->method('andWhere')->willReturnSelf();
		$query->method('setMaxResults')->willReturnSelf();
		$query->method('groupBy')->willReturnSelf();
		$query->method('runAcrossAllShards')->willReturnSelf();
		$query->method('createNamedParameter')
			->willReturn($this->createMock(IParameter::class));
		$query->method('executeQuery')->willReturn($result);

		$result->expects($this->once())
			->method('fetchAssociative')
			->willReturn(['user_id' => 'foouser']);
		$result->expects($this->once())
			->method('closeCursor')
			->willReturn(true);

		$job = new ScanFiles(
			$this->createMock(IConfig::class),
			$this->createMock(IEventDispatcher::class),
			$this->createMock(LoggerInterface::class),
			$connection,
			$this->createMock(ITimeFactory::class),
			$this->createMock(SetupManager::class),
			$this->createMock(IUserManager::class),
		);

		$this->assertSame('foouser', self::invokePrivate($job, 'getUserToScan'));
	}

	/**
	 * @param int[] $storages
	 * @param list<string|false> $chunkResults One result per fallback query executed
	 */
	private function assertShardedFallback(
		array $storages,
		array $chunkResults,
		string|false $expectedUser,
	): void {
		$connection = $this->createMock(IDBConnection::class);
		$connection->method('getShardDefinition')
			->with('filecache')
			->willReturn($this->createMock(ShardDefinition::class));

		$firstQuery = $this->createMock(IQueryBuilder::class);
		$firstResult = $this->createMock(IResult::class);
		$firstResult->method('fetchAssociative')->willReturn(false);
		$firstResult->expects($this->once())->method('closeCursor')->willReturn(true);
		$firstQuery->method('executeQuery')->willReturn($firstResult);

		$mountQuery = $this->createMock(IQueryBuilder::class);
		$mountResult = $this->createMock(IResult::class);
		$mountResult->method('fetchFirstColumn')->willReturn($storages);
		$mountQuery->method('executeQuery')->willReturn($mountResult);

		$boundChunks = [];
		$fallbackQueries = [];
		foreach ($chunkResults as $user) {
			$query = $this->createMock(IQueryBuilder::class);
			$result = $this->createMock(IResult::class);
			$result->method('fetchOne')->willReturn($user);
			$result->expects($this->once())->method('closeCursor')->willReturn(true);
			$query->method('executeQuery')->willReturn($result);
			$query->method('createNamedParameter')
				->willReturnCallback(function (mixed $value, mixed $type) use (&$boundChunks): string {
					if (is_array($value)) {
						$this->assertSame(IQueryBuilder::PARAM_INT_ARRAY, $type);
						$boundChunks[] = $value;
					}
					return ':param';
				});
			$fallbackQueries[] = $query;
		}

		$queries = [$firstQuery, $mountQuery, ...$fallbackQueries];
		$connection->expects($this->exactly(count($queries)))
			->method('getQueryBuilder')
			->willReturnOnConsecutiveCalls(...$queries);

		foreach ($queries as $query) {
			$query->method('expr')->willReturn($this->createMock(IExpressionBuilder::class));
			$query->method('select')->willReturnSelf();
			$query->method('selectDistinct')->willReturnSelf();
			$query->method('from')->willReturnSelf();
			$query->method('leftJoin')->willReturnSelf();
			$query->method('where')->willReturnSelf();
			$query->method('andWhere')->willReturnSelf();
			$query->method('groupBy')->willReturnSelf();
			$query->method('setMaxResults')->willReturnSelf();
			$query->method('runAcrossAllShards')->willReturnSelf();
		}

		$job = new ScanFiles(
			$this->createMock(IConfig::class),
			$this->createMock(IEventDispatcher::class),
			$this->createMock(LoggerInterface::class),
			$connection,
			$this->createMock(ITimeFactory::class),
			$this->createMock(SetupManager::class),
			$this->createMock(IUserManager::class),
		);

		$this->assertSame($expectedUser, self::invokePrivate($job, 'getUserToScan'));
		$expectedChunks = array_slice(
			array_chunk($storages, IQueryBuilder::MAX_IN_PARAMETERS),
			0,
			count($chunkResults),
		);
		$this->assertSame($expectedChunks, $boundChunks);
	}

	public function testShardedFallbackWithNoMountedStorages(): void {
		$this->assertShardedFallback([], [], false);
	}

	public function testShardedFallbackFindsUserInSecondChunk(): void {
		$this->assertShardedFallback(
			range(1, IQueryBuilder::MAX_IN_PARAMETERS + 1),
			[false, 'foouser'],
			'foouser',
		);
	}

	public function testShardedFallbackChecksEveryChunkWithoutMatch(): void {
		$this->assertShardedFallback(
			range(1, IQueryBuilder::MAX_IN_PARAMETERS * 2 + 1),
			[false, false, false],
			false,
		);
	}
}
