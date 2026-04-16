<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace Test\lib\Config;

use Doctrine\DBAL\Exception\InvalidFieldNameException;
use OC\Config\ConfigManager;
use OC\Config\PresetManager;
use OC\Config\UserConfig;
use OC\DB\Exceptions\DbalException;
use OCP\DB\Exception as DBException;
use OCP\DB\IResult;
use OCP\DB\QueryBuilder\IExpressionBuilder;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\Security\ICrypto;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

/**
 * Tests the ownCloud migration fallback in UserConfig.
 *
 * When migrating from ownCloud, the preferences table lacks 'type', 'lazy',
 * 'flags', and 'indexed' columns. UserConfig::loadConfig() must catch the
 * resulting DBException and retry with a query that only selects columns
 * present in ownCloud's schema.
 */
class UserConfigMigrationFallbackTest extends TestCase {
	private IDBConnection&MockObject $connection;
	private IConfig&MockObject $config;
	private ConfigManager&MockObject $configManager;
	private PresetManager&MockObject $presetManager;
	private LoggerInterface&MockObject $logger;
	private ICrypto&MockObject $crypto;
	private IEventDispatcher&MockObject $dispatcher;

	protected function setUp(): void {
		parent::setUp();

		$this->connection = $this->createMock(IDBConnection::class);
		$this->config = $this->createMock(IConfig::class);
		$this->configManager = $this->createMock(ConfigManager::class);
		$this->presetManager = $this->createMock(PresetManager::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->crypto = $this->createMock(ICrypto::class);
		$this->dispatcher = $this->createMock(IEventDispatcher::class);
	}

	private function getUserConfig(): UserConfig {
		return new UserConfig(
			$this->connection,
			$this->config,
			$this->configManager,
			$this->presetManager,
			$this->logger,
			$this->crypto,
			$this->dispatcher,
		);
	}

	private function createInvalidFieldNameException(): DBException {
		$driverException = $this->createMock(\Doctrine\DBAL\Driver\Exception::class);
		$dbalException = new InvalidFieldNameException($driverException, null);
		return DbalException::wrap($dbalException);
	}

	private function createMockQueryBuilder(): IQueryBuilder&MockObject {
		$expression = $this->createMock(IExpressionBuilder::class);
		$qb = $this->createMock(IQueryBuilder::class);
		$qb->method('from')->willReturn($qb);
		$qb->method('select')->willReturn($qb);
		$qb->method('addSelect')->willReturn($qb);
		$qb->method('where')->willReturn($qb);
		$qb->method('andWhere')->willReturn($qb);
		$qb->method('set')->willReturn($qb);
		$qb->method('expr')->willReturn($expression);
		$qb->method('insert')->willReturn($qb);
		$qb->method('setValue')->willReturn($qb);
		$qb->method('createNamedParameter')->willReturn('?');
		return $qb;
	}

	/**
	 * Test that loadConfig retries without new columns on InvalidFieldNameException.
	 */
	public function testLoadConfigFallsBackOnMissingColumns(): void {
		$exception = $this->createInvalidFieldNameException();

		$successResult = $this->createMock(IResult::class);
		$successResult->method('fetchAll')->willReturn([
			['appid' => 'settings', 'configkey' => 'email', 'configvalue' => 'user@example.com'],
		]);

		$qb = $this->createMockQueryBuilder();
		$qb->method('executeQuery')
			->willReturnOnConsecutiveCalls(
				$this->throwException($exception),
				$successResult,
			);

		$this->connection->method('getQueryBuilder')->willReturn($qb);

		$userConfig = $this->getUserConfig();

		$value = $userConfig->getValueString('user1', 'settings', 'email');
		$this->assertSame('user@example.com', $value);
	}

	/**
	 * Test that non-INVALID_FIELD_NAME exceptions are re-thrown.
	 */
	public function testLoadConfigRethrowsOtherExceptions(): void {
		$driverException = $this->createMock(\Doctrine\DBAL\Driver\Exception::class);
		$dbalException = new \Doctrine\DBAL\Exception\SyntaxErrorException($driverException, null);
		$exception = DbalException::wrap($dbalException);

		$qb = $this->createMockQueryBuilder();
		$qb->method('executeQuery')->willThrowException($exception);

		$this->connection->method('getQueryBuilder')->willReturn($qb);

		$userConfig = $this->getUserConfig();

		$this->expectException(DBException::class);
		$userConfig->getValueString('user1', 'settings', 'email');
	}

	/**
	 * Test that insert omits new columns when migration is not completed.
	 */
	public function testInsertOmitsNewColumnsInFallbackMode(): void {
		$exception = $this->createInvalidFieldNameException();

		$loadResult = $this->createMock(IResult::class);
		$loadResult->method('fetchAll')->willReturn([]);

		$qb = $this->createMockQueryBuilder();

		$callCount = 0;
		$qb->method('executeQuery')
			->willReturnCallback(function () use ($exception, $loadResult, &$callCount) {
				$callCount++;
				if ($callCount === 1) {
					throw $exception;
				}
				return $loadResult;
			});

		$qb->expects(self::once())->method('insert')->with('preferences')->willReturn($qb);
		$qb->method('executeStatement')->willReturn(1);

		$setColumns = [];
		$qb->method('setValue')
			->willReturnCallback(function (string $column) use ($qb, &$setColumns) {
				$setColumns[] = $column;
				return $qb;
			});

		$this->connection->method('getQueryBuilder')->willReturn($qb);

		$userConfig = $this->getUserConfig();
		$userConfig->setValueString('user1', 'settings', 'email', 'user@example.com');

		$this->assertContains('userid', $setColumns);
		$this->assertContains('appid', $setColumns);
		$this->assertContains('configkey', $setColumns);
		$this->assertContains('configvalue', $setColumns);
		$this->assertNotContains('lazy', $setColumns, 'lazy column should be omitted in fallback mode');
		$this->assertNotContains('type', $setColumns, 'type column should be omitted in fallback mode');
		$this->assertNotContains('flags', $setColumns, 'flags column should be omitted in fallback mode');
		$this->assertNotContains('indexed', $setColumns, 'indexed column should be omitted in fallback mode');
	}
}
