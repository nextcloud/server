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
use OC\Memcache\Factory as CacheFactory;
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
 * Class AppConfigTest
 *
 * @package Test
 */
class AppConfigTest extends TestCase {
	private IConfig&MockObject $config;
	private IDBConnection&MockObject $connection;
	private ConfigManager&MockObject $configManager;
	private PresetManager&MockObject $presetManager;
	private LoggerInterface&MockObject $logger;
	private ICrypto&MockObject $crypto;
	private CacheFactory&MockObject $cacheFactory;
	private ICache&MockObject $localCache;

	protected function setUp(): void {
		parent::setUp();

		$this->connection = $this->createMock(IDBConnection::class);
		$this->config = $this->createMock(IConfig::class);
		$this->configManager = $this->createMock(ConfigManager::class);
		$this->presetManager = $this->createMock(PresetManager::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->crypto = $this->createMock(ICrypto::class);
		$this->cacheFactory = $this->createMock(CacheFactory::class);
		$this->localCache = $this->createMock(ICache::class);
	}

	protected function getAppConfig($cached = false): AppConfig {
		$this->config->method('getSystemValueBool')
			->with('cache_app_config', $cached)
			->willReturn(true);
		$this->cacheFactory->method('isLocalCacheAvailable')->willReturn($cached);
		if ($cached) {
			$this->cacheFactory->method('withServerVersionPrefix')->willReturnCallback(function (\Closure $closure): void {
				$closure($this->cacheFactory);
			});
			$this->cacheFactory->method('createLocal')->willReturn($this->localCache);
		}

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

	public function testCachedRead(): void {
		$this->localCache->expects(self::once())
			->method('get')
			->with('OC\\AppConfig')
			->willReturn([
				'fastCache' => [
					'appid' => [
						'some-key' => 'some-value',
						'other-key' => 'other value'
					],
				],
				'valueTypes' => [
					'appid' => [
						'some-key' => AppConfig::VALUE_STRING,
						'other-key' => AppConfig::VALUE_STRING,
					],
				],
			]);

		$this->connection->expects(self::never())->method('getQueryBuilder');
		$config = $this->getAppConfig(true);


		$this->assertSame('some-value', $config->getValueString('appid', 'some-key'));
		$this->assertSame('other value', $config->getValueString('appid', 'other-key'));
		$this->assertSame(AppConfig::VALUE_STRING, $config->getValueType('appid', 'some-key', false));
	}

	public function testCachedLazyRead(): void {
		$this->localCache->expects(self::once())
			->method('get')
			->with('OC\\AppConfig')
			->willReturn([
				'fastCache' => [
					'appid' => [
						'fast-key' => 'fast value',
					],
				],
				'lazyCache' => [
					'appid' => [
						'lazy-key' => 'lazy value',
					],
				],
				'valueTypes' => [
					'appid' => [
						'some-key' => AppConfig::VALUE_STRING,
						'lazy-key' => AppConfig::VALUE_STRING,
					],
				],
			]);

		$this->connection->expects(self::never())->method('getQueryBuilder');
		$config = $this->getAppConfig(true);


		$this->assertSame('fast value', $config->getValueString('appid', 'fast-key'));
		$this->assertSame('lazy value', $config->getValueString('appid', 'lazy-key', '', true));
	}

	public function testOnlyFastKeyCached(): void {
		$this->localCache->expects(self::atLeastOnce())
			->method('get')
			->with('OC\\AppConfig')
			->willReturn([
				'fastCache' => [
					'appid' => [
						'fast-key' => 'fast value',
					],
				],
				'valueTypes' => [
					'appid' => [
						'fast-key' => AppConfig::VALUE_STRING,
					],
				],
			]);

		$result = $this->createMock(IResult::class);
		$result->method('fetchAll')->willReturn([
			['lazy' => 1, 'appid' => 'appid', 'configkey' => 'lazy-key', 'configvalue' => 'lazy value'],
		]);
		$expression = $this->createMock(IExpressionBuilder::class);
		$queryBuilder = $this->createMock(IQueryBuilder::class);
		$queryBuilder->method('from')->willReturn($queryBuilder);
		$queryBuilder->method('expr')->willReturn($expression);
		$queryBuilder->method('executeQuery')->willReturn($result);

		$this->connection->expects(self::once())->method('getQueryBuilder')->willReturn($queryBuilder);
		$config = $this->getAppConfig(true);


		$this->assertSame('fast value', $config->getValueString('appid', 'fast-key'));
		$this->assertSame('lazy value', $config->getValueString('appid', 'lazy-key', '', true));
	}

	public function testWritesAreCached(): void {
		$this->localCache->expects(self::atLeastOnce())
			->method('get')
			->with('OC\\AppConfig')
			->willReturn([
				'fastCache' => [
					'appid' => [
						'first-key' => 'first value',
					],
				],
				'valueTypes' => [
					'appid' => [
						'first-key' => AppConfig::VALUE_STRING,
					],
				],
			]);

		$expression = $this->createMock(IExpressionBuilder::class);
		$queryBuilder = $this->createMock(IQueryBuilder::class);
		$queryBuilder->expects(self::once())
			->method('update')
			->with('appconfig', null)
			->willReturn($queryBuilder);
		$queryBuilder->method('set')->willReturn($queryBuilder);
		$queryBuilder->method('where')->willReturn($queryBuilder);
		$queryBuilder->method('andWhere')->willReturn($queryBuilder);
		$queryBuilder->method('expr')->willReturn($expression);
		$this->connection->expects(self::once())->method('getQueryBuilder')->willReturn($queryBuilder);

		$config = $this->getAppConfig(true);

		$this->assertSame('first value', $config->getValueString('appid', 'first-key'));
		$config->setValueString('appid', 'first-key', 'new value');
		$this->assertSame('new value', $config->getValueString('appid', 'first-key'));
	}
}
