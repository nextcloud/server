<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\App;

use OC\App\AppDataPurger;
use OC\DB\SchemaChecker;
use OCP\App\IAppManager;
use OCP\BackgroundJob\IJobList;
use OCP\Config\IUserConfig;
use OCP\Files\AppData\IAppDataFactory;
use OCP\Files\IRootFolder;
use OCP\IAppConfig;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\Server;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

#[\PHPUnit\Framework\Attributes\Group('DB')]
class AppDataPurgerTest extends TestCase {
	private const APP = 'purgetest';
	// shares the app's name as a prefix, and must survive its purge
	private const OTHER_APP = 'purgetestother';

	private IDBConnection $connection;
	private IAppConfig $appConfig;
	private IUserConfig $userConfig;
	private IAppManager&MockObject $appManager;
	private SchemaChecker&MockObject $schemaChecker;
	private AppDataPurger $purger;
	private string $prefix;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->connection = Server::get(IDBConnection::class);
		$this->appConfig = Server::get(IAppConfig::class);
		$this->userConfig = Server::get(IUserConfig::class);
		$this->prefix = Server::get(IConfig::class)->getSystemValueString('dbtableprefix', 'oc_');

		$this->appManager = $this->createMock(IAppManager::class);
		$this->appManager->method('cleanAppId')->willReturnCallback(fn (string $app) => preg_replace('/[^a-z0-9_-]+/', '', $app));
		$this->appManager->method('isShipped')->willReturn(false);
		$this->appManager->method('isEnabledForAnyone')->willReturnCallback(fn (string $app) => !in_array($app, [self::APP, self::OTHER_APP], true));
		$this->appManager->method('getAppNamespace')->willReturnCallback(fn (string $app) => 'OCA\\' . ucfirst($app));

		$this->schemaChecker = $this->createMock(SchemaChecker::class);
		$this->schemaChecker->method('getTablesCreatedBy')->willReturnCallback(fn (string $app) => match ($app) {
			self::APP => [$this->prefix . 'purgetest_items', $this->prefix . 'purgetest_never_created'],
			default => [],
		});

		$this->purger = new AppDataPurger(
			$this->appManager,
			$this->appConfig,
			$this->userConfig,
			Server::get(IConfig::class),
			$this->connection,
			$this->schemaChecker,
			Server::get(IRootFolder::class),
			$this->createMock(LoggerInterface::class),
		);

		$this->leaveDataBehind(self::APP);
		$this->leaveDataBehind(self::OTHER_APP);
		$this->connection->executeStatement('CREATE TABLE *PREFIX*purgetest_items (id INTEGER NOT NULL)');
		$this->connection->executeStatement('INSERT INTO *PREFIX*purgetest_items (id) VALUES (1)');
	}

	#[\Override]
	protected function tearDown(): void {
		foreach ([self::APP, self::OTHER_APP] as $app) {
			$this->appConfig->deleteApp($app);
			$this->userConfig->deleteApp($app);
			foreach (['migrations' => 'app'] as $table => $column) {
				$qb = $this->connection->getQueryBuilder();
				$qb->delete($table)->where($qb->expr()->eq($column, $qb->createNamedParameter($app)))->executeStatement();
			}
			$qb = $this->connection->getQueryBuilder();
			$qb->delete('jobs')->where($qb->expr()->like('class', $qb->createNamedParameter('OCA\\\\' . ucfirst($app) . '%')))->executeStatement();
		}
		$this->connection->dropTable('purgetest_items');
		parent::tearDown();
	}

	private function leaveDataBehind(string $app): void {
		$this->appConfig->setValueString($app, 'installed_version', '1.2.3');
		$this->appConfig->setValueString($app, 'some_setting', 'on');
		$this->userConfig->setValueString('purge-user', $app, 'theme', 'dark');

		$qb = $this->connection->getQueryBuilder();
		$qb->insert('migrations')->values(['app' => $qb->createNamedParameter($app), 'version' => $qb->createNamedParameter('1000Date20260101000000')])->executeStatement();

		Server::get(IJobList::class)->add('OCA\\' . ucfirst($app) . '\\BackgroundJob\\Cleanup');

		Server::get(IAppDataFactory::class)->get($app)->newFolder('cache')->newFile('entry.json', '{"cached":true}');
	}

	private function countRows(string $table, string $column, string $value): int {
		$qb = $this->connection->getQueryBuilder();
		$qb->select($qb->func()->count('*'))->from($table)->where($qb->expr()->like($column, $qb->createNamedParameter($value)));
		return (int)$qb->executeQuery()->fetchOne();
	}

	public function testFindsWhatAnAppLeftBehind(): void {
		$leftovers = $this->purger->getLeftovers(self::APP);

		$this->assertTrue($leftovers->codePresent);
		// only the tables that exist
		$this->assertSame([$this->prefix . 'purgetest_items'], $leftovers->tables);
		$this->assertSame(2, $leftovers->configKeys);
		$this->assertSame(1, $leftovers->userPreferences);
		$this->assertSame(1, $leftovers->backgroundJobs);
		$this->assertSame(1, $leftovers->migrations);
		$this->assertSame(strlen('{"cached":true}'), $leftovers->appDataSize);
		$this->assertFalse($leftovers->isEmpty());
	}

	public function testPurgeDeletesAllOfItAndNothingElse(): void {
		$this->purger->purge($this->purger->getLeftovers(self::APP));

		$this->assertFalse($this->connection->tableExists('purgetest_items'));
		$this->assertSame([], $this->appConfig->getKeys(self::APP));
		$this->assertSame(0, $this->countRows('preferences', 'appid', self::APP));
		$this->assertSame(0, $this->countRows('migrations', 'app', self::APP));
		$this->assertSame(0, $this->countRows('jobs', 'class', 'OCA\\\\Purgetest\\\\%'));
		$this->assertTrue($this->purger->getLeftovers(self::APP)->isEmpty());

		// the other app, whose name starts the same way, keeps everything
		$other = $this->purger->getLeftovers(self::OTHER_APP);
		$this->assertSame(2, $other->configKeys);
		$this->assertSame(1, $other->userPreferences);
		$this->assertSame(1, $other->backgroundJobs);
		$this->assertSame(1, $other->migrations);
		$this->assertNotNull($other->appDataSize);
	}

	public function testListsAppsWithLeftovers(): void {
		$apps = array_map(fn ($l) => $l->appId, $this->purger->findAppsWithLeftovers());
		$this->assertSame([self::APP, self::OTHER_APP], $apps);
	}

	public function testRemovedAppsHaveNoKnownTables(): void {
		$schemaChecker = $this->createMock(SchemaChecker::class);
		$schemaChecker->method('getTablesCreatedBy')->willReturn(null);
		$purger = new AppDataPurger($this->appManager, $this->appConfig, $this->userConfig, Server::get(IConfig::class),
			$this->connection, $schemaChecker, Server::get(IRootFolder::class), $this->createMock(LoggerInterface::class));

		$leftovers = $purger->getLeftovers(self::APP);
		$this->assertFalse($leftovers->codePresent);
		$this->assertSame([], $leftovers->tables);
		$this->assertSame(2, $leftovers->configKeys);
	}

	public function testMigrationsThatCannotBeReplayedStopThePurge(): void {
		$schemaChecker = $this->createMock(SchemaChecker::class);
		$schemaChecker->method('getTablesCreatedBy')->willThrowException(new \Error('Class "OCA\\Purgetest\\Db\\TableManager" not found'));
		$purger = new AppDataPurger($this->appManager, $this->appConfig, $this->userConfig, Server::get(IConfig::class),
			$this->connection, $schemaChecker, Server::get(IRootFolder::class), $this->createMock(LoggerInterface::class));

		$this->expectException(\RuntimeException::class);
		$this->expectExceptionMessage("Could not find the tables of app 'purgetest'");
		$purger->getLeftovers(self::APP);
	}

	public static function dataRefusals(): array {
		return [
			'core' => ['core', 'shipped with Nextcloud'],
			'enabled' => ['files_somewhere', 'is enabled'],
			'path traversal' => ['../purgetest', 'not a valid app id'],
			'empty' => ['', 'not a valid app id'],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataRefusals')]
	public function testRefusals(string $appId, string $reason): void {
		$this->assertStringContainsString($reason, (string)$this->purger->getRefusal($appId));
	}

	public function testPurgeChecksTheRefusalsItself(): void {
		$leftovers = $this->purger->getLeftovers(self::APP);
		$appManager = $this->createMock(IAppManager::class);
		$appManager->method('cleanAppId')->willReturnArgument(0);
		$appManager->method('isEnabledForAnyone')->willReturn(true);
		$purger = new AppDataPurger($appManager, $this->appConfig, $this->userConfig, Server::get(IConfig::class),
			$this->connection, $this->schemaChecker, Server::get(IRootFolder::class), $this->createMock(LoggerInterface::class));

		$this->expectException(\InvalidArgumentException::class);
		$purger->purge($leftovers);
	}
}
