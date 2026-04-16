<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace Test;

use OC\AppConfig;
use OC\Config\ConfigManager;
use OC\Config\PresetManager;
use OC\DB\Exceptions\DbalException;
use OC\Memcache\Factory as CacheFactory;
use Doctrine\DBAL\Exception\InvalidFieldNameException;
use OCP\DB\Exception as DBException;
use OCP\DB\IResult;
use OCP\DB\QueryBuilder\IExpressionBuilder;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\ICache;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\Security\ICrypto;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

/**
 * Tests the ownCloud migration fallback in AppConfig.
 *
 * When migrating from ownCloud, the appconfig table lacks 'type' and 'lazy'
 * columns. AppConfig::loadConfig() must catch the resulting DBException and
 * retry with a query that only selects columns present in ownCloud's schema.
 */
class AppConfigMigrationFallbackTest extends TestCase {
	private IConfig&MockObject $config;
	private IDBConnection&MockObject $connection;
	private ConfigManager&MockObject $configManager;
	private PresetManager&MockObject $presetManager;
	private LoggerInterface&MockObject $logger;
	private ICrypto&MockObject $crypto;
	private CacheFactory&MockObject $cacheFactory;

	protected function setUp(): void {
		parent::setUp();

		$this->connection = $this->createMock(IDBConnection::class);
		$this->config = $this->createMock(IConfig::class);
		$this->configManager = $this->createMock(ConfigManager::class);
		$this->presetManager = $this->createMock(PresetManager::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->crypto = $this->createMock(ICrypto::class);
		$this->cacheFactory = $this->createMock(CacheFactory::class);
	}

	private function getAppConfig(): AppConfig {
		$this->config->method('getSystemValueBool')
			->with('cache_app_config', true)
			->willReturn(true);
		$this->cacheFactory->method('isLocalCacheAvailable')->willReturn(false);

		return new AppConfig(
			$this->connection,
			$this->config,
			$this->configManager,
			$this->presetManager,
			$this->logger,
			$this->crypto,
			$this->cacheFactory,
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
	 * Test that loadConfig retries without type/lazy columns on InvalidFieldNameException.
	 */
	public function testLoadConfigFallsBackOnMissingColumns(): void {
		$exception = $this->createInvalidFieldNameException();

		$successResult = $this->createMock(IResult::class);
		$successResult->method('fetchAll')->willReturn([
			['appid' => 'core', 'configkey' => 'vendor', 'configvalue' => 'owncloud'],
			['appid' => 'core', 'configkey' => 'installedat', 'configvalue' => '1234567890'],
		]);

		$qb = $this->createMockQueryBuilder();
		// First call throws (columns missing), second call succeeds (fallback query)
		$qb->method('executeQuery')
			->willReturnOnConsecutiveCalls(
				$this->throwException($exception),
				$successResult,
			);

		$this->connection->method('getQueryBuilder')->willReturn($qb);

		$appConfig = $this->getAppConfig();

		// getValueString triggers loadConfig internally
		$value = $appConfig->getValueString('core', 'vendor');
		$this->assertSame('owncloud', $value);
	}

	/**
	 * Test that non-INVALID_FIELD_NAME exceptions are re-thrown, not swallowed.
	 */
	public function testLoadConfigRethrowsOtherExceptions(): void {
		$driverException = $this->createMock(\Doctrine\DBAL\Driver\Exception::class);
		$dbalException = new \Doctrine\DBAL\Exception\SyntaxErrorException($driverException, null);
		$exception = DbalException::wrap($dbalException);

		$qb = $this->createMockQueryBuilder();
		$qb->method('executeQuery')->willThrowException($exception);

		$this->connection->method('getQueryBuilder')->willReturn($qb);

		$appConfig = $this->getAppConfig();

		$this->expectException(DBException::class);
		$appConfig->getValueString('core', 'vendor');
	}

	/**
	 * Test that insert omits lazy/type columns when migration is not completed.
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

		// Verify insert() is called (meaning we reached the insert path)
		$qb->expects(self::once())->method('insert')->with('appconfig')->willReturn($qb);
		$qb->method('executeStatement')->willReturn(1);

		// Track which columns are set via setValue
		$setColumns = [];
		$qb->method('setValue')
			->willReturnCallback(function (string $column) use ($qb, &$setColumns) {
				$setColumns[] = $column;
				return $qb;
			});

		$this->connection->method('getQueryBuilder')->willReturn($qb);

		$appConfig = $this->getAppConfig();
		$appConfig->setValueString('core', 'vendor', 'owncloud');

		$this->assertContains('appid', $setColumns);
		$this->assertContains('configkey', $setColumns);
		$this->assertContains('configvalue', $setColumns);
		$this->assertNotContains('lazy', $setColumns, 'lazy column should be omitted in fallback mode');
		$this->assertNotContains('type', $setColumns, 'type column should be omitted in fallback mode');
	}
}
