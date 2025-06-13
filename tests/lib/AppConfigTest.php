<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace Test;

use InvalidArgumentException;
use OC\AppConfig;
use OCP\Exceptions\AppConfigTypeConflictException;
use OCP\Exceptions\AppConfigUnknownKeyException;
use OCP\IAppConfig;
use OCP\IDBConnection;
use OCP\Security\ICrypto;
use OCP\Server;
use Psr\Log\LoggerInterface;

/**
 * Class AppConfigTest
 *
 * @group DB
 *
 * @package Test
 */
class AppConfigTest extends TestCase {
	protected IAppConfig $appConfig;
	protected IDBConnection $connection;
	private LoggerInterface $logger;
	private ICrypto $crypto;

	private array $originalConfig;

	/**
	 * @var array<string, array<string, array<string, string, int, bool, bool>>>
	 *                                                                           [appId => [configKey, configValue, valueType, lazy, sensitive]]
	 */
	private static array $baseStruct =
		[
			'testapp' => [
				'enabled' => ['enabled', 'yes'],
				'installed_version' => ['installed_version', '1.2.3'],
				'depends_on' => ['depends_on', 'someapp'],
				'deletethis' => ['deletethis', 'deletethis'],
				'key' => ['key', 'value']
			],
			'someapp' => [
				'key' => ['key', 'value'],
				'otherkey' => ['otherkey', 'othervalue']
			],
			'123456' => [
				'enabled' => ['enabled', 'yes'],
				'key' => ['key', 'value']
			],
			'anotherapp' => [
				'enabled' => ['enabled', 'no'],
				'installed_version' => ['installed_version', '3.2.1'],
				'key' => ['key', 'value']
			],
			'non-sensitive-app' => [
				'lazy-key' => ['lazy-key', 'value', IAppConfig::VALUE_STRING, true, false],
				'non-lazy-key' => ['non-lazy-key', 'value', IAppConfig::VALUE_STRING, false, false],
			],
			'sensitive-app' => [
				'lazy-key' => ['lazy-key', 'value', IAppConfig::VALUE_STRING, true, true],
				'non-lazy-key' => ['non-lazy-key', 'value', IAppConfig::VALUE_STRING, false, true],
			],
			'only-lazy' => [
				'lazy-key' => ['lazy-key', 'value', IAppConfig::VALUE_STRING, true]
			],
			'typed' => [
				'mixed' => ['mixed', 'mix', IAppConfig::VALUE_MIXED],
				'string' => ['string', 'value', IAppConfig::VALUE_STRING],
				'int' => ['int', '42', IAppConfig::VALUE_INT],
				'float' => ['float', '3.14', IAppConfig::VALUE_FLOAT],
				'bool' => ['bool', '1', IAppConfig::VALUE_BOOL],
				'array' => ['array', '{"test": 1}', IAppConfig::VALUE_ARRAY],
			],
			'prefix-app' => [
				'key1' => ['key1', 'value'],
				'prefix1' => ['prefix1', 'value'],
				'prefix-2' => ['prefix-2', 'value'],
				'key-2' => ['key-2', 'value'],
			]
		];

	protected function setUp(): void {
		parent::setUp();

		$this->connection = Server::get(IDBConnection::class);
		$this->logger = Server::get(LoggerInterface::class);
		$this->crypto = Server::get(ICrypto::class);

		// storing current config and emptying the data table
		$sql = $this->connection->getQueryBuilder();
		$sql->select('*')
			->from('appconfig');
		$result = $sql->executeQuery();
		$this->originalConfig = $result->fetchAll();
		$result->closeCursor();

		$sql = $this->connection->getQueryBuilder();
		$sql->delete('appconfig');
		$sql->executeStatement();

		$sql = $this->connection->getQueryBuilder();
		$sql->insert('appconfig')
			->values(
				[
					'appid' => $sql->createParameter('appid'),
					'configkey' => $sql->createParameter('configkey'),
					'configvalue' => $sql->createParameter('configvalue'),
					'type' => $sql->createParameter('type'),
					'lazy' => $sql->createParameter('lazy')
				]
			);

		foreach (self::$baseStruct as $appId => $appData) {
			foreach ($appData as $key => $row) {
				$value = $row[1];
				$type = $row[2] ?? IAppConfig::VALUE_MIXED;
				if (($row[4] ?? false) === true) {
					$type |= IAppConfig::VALUE_SENSITIVE;
					$value = self::invokePrivate(AppConfig::class, 'ENCRYPTION_PREFIX') . $this->crypto->encrypt($value);
					self::$baseStruct[$appId][$key]['encrypted'] = $value;
				}

				$sql->setParameters(
					[
						'appid' => $appId,
						'configkey' => $row[0],
						'configvalue' => $value,
						'type' => $type,
						'lazy' => (($row[3] ?? false) === true) ? 1 : 0
					]
				)->executeStatement();
			}
		}
	}

	protected function tearDown(): void {
		$sql = $this->connection->getQueryBuilder();
		$sql->delete('appconfig');
		$sql->executeStatement();

		$sql = $this->connection->getQueryBuilder();
		$sql->insert('appconfig')
			->values(
				[
					'appid' => $sql->createParameter('appid'),
					'configkey' => $sql->createParameter('configkey'),
					'configvalue' => $sql->createParameter('configvalue'),
					'lazy' => $sql->createParameter('lazy'),
					'type' => $sql->createParameter('type'),
				]
			);

		foreach ($this->originalConfig as $key => $configs) {
			$sql->setParameter('appid', $configs['appid'])
				->setParameter('configkey', $configs['configkey'])
				->setParameter('configvalue', $configs['configvalue'])
				->setParameter('lazy', ($configs['lazy'] === '1') ? '1' : '0')
				->setParameter('type', $configs['type']);
			$sql->executeStatement();
		}

		//		$this->restoreService(AppConfig::class);
		parent::tearDown();
	}

	/**
	 * @param bool $preLoading TRUE will preload the 'fast' cache, which is the normal behavior of usual
	 *                         IAppConfig
	 *
	 * @return IAppConfig
	 */
	private function generateAppConfig(bool $preLoading = true): IAppConfig {
		/** @var AppConfig $config */
		$config = new AppConfig(
			$this->connection,
			$this->logger,
			$this->crypto,
		);
		$msg = ' generateAppConfig() failed to confirm cache status';

		// confirm cache status
		$status = $config->statusCache();
		$this->assertSame(false, $status['fastLoaded'], $msg);
		$this->assertSame(false, $status['lazyLoaded'], $msg);
		$this->assertSame([], $status['fastCache'], $msg);
		$this->assertSame([], $status['lazyCache'], $msg);
		if ($preLoading) {
			// simple way to initiate the load of non-lazy config values in cache
			$config->getValueString('core', 'preload', '');

			// confirm cache status
			$status = $config->statusCache();
			$this->assertSame(true, $status['fastLoaded'], $msg);
			$this->assertSame(false, $status['lazyLoaded'], $msg);

			$apps = array_values(array_diff(array_keys(self::$baseStruct), ['only-lazy']));
			$this->assertEqualsCanonicalizing($apps, array_keys($status['fastCache']), $msg);
			$this->assertSame([], array_keys($status['lazyCache']), $msg);
		}

		return $config;
	}

	public function testGetApps(): void {
		$config = $this->generateAppConfig(false);

		$this->assertEqualsCanonicalizing(array_keys(self::$baseStruct), $config->getApps());
	}

	public function testGetAppInstalledVersions(): void {
		$config = $this->generateAppConfig(false);

		$this->assertEquals(
			['testapp' => '1.2.3', 'anotherapp' => '3.2.1'],
			$config->getAppInstalledVersions(false)
		);
		$this->assertEquals(
			['testapp' => '1.2.3'],
			$config->getAppInstalledVersions(true)
		);
	}

	/**
	 * returns list of app and their keys
	 *
	 * @return array<string, string[]> ['appId' => ['key1', 'key2', ]]
	 * @see testGetKeys
	 */
	public static function providerGetAppKeys(): array {
		$appKeys = [];
		foreach (self::$baseStruct as $appId => $appData) {
			$keys = [];
			foreach ($appData as $row) {
				$keys[] = $row[0];
			}
			$appKeys[] = [(string)$appId, $keys];
		}

		return $appKeys;
	}

	/**
	 * returns list of config keys
	 *
	 * @return array<string, string, string, int, bool, bool> [appId, key, value, type, lazy, sensitive]
	 * @see testIsSensitive
	 * @see testIsLazy
	 * @see testGetKeys
	 */
	public static function providerGetKeys(): array {
		$appKeys = [];
		foreach (self::$baseStruct as $appId => $appData) {
			foreach ($appData as $row) {
				$appKeys[] = [
					(string)$appId, $row[0], $row[1], $row[2] ?? IAppConfig::VALUE_MIXED, $row[3] ?? false,
					$row[4] ?? false
				];
			}
		}

		return $appKeys;
	}

	/**
	 * @dataProvider providerGetAppKeys
	 *
	 * @param string $appId
	 * @param array $expectedKeys
	 */
	public function testGetKeys(string $appId, array $expectedKeys): void {
		$config = $this->generateAppConfig();
		$this->assertEqualsCanonicalizing($expectedKeys, $config->getKeys($appId));
	}

	public function testGetKeysOnUnknownAppShouldReturnsEmptyArray(): void {
		$config = $this->generateAppConfig();
		$this->assertEqualsCanonicalizing([], $config->getKeys('unknown-app'));
	}

	/**
	 * @dataProvider providerGetKeys
	 *
	 * @param string $appId
	 * @param string $configKey
	 * @param string $value
	 * @param bool $lazy
	 */
	public function testHasKey(string $appId, string $configKey, string $value, int $type, bool $lazy): void {
		$config = $this->generateAppConfig();
		$this->assertEquals(true, $config->hasKey($appId, $configKey, $lazy));
	}

	public function testHasKeyOnNonExistentKeyReturnsFalse(): void {
		$config = $this->generateAppConfig();
		$this->assertEquals(false, $config->hasKey(array_keys(self::$baseStruct)[0], 'inexistant-key'));
	}

	public function testHasKeyOnUnknownAppReturnsFalse(): void {
		$config = $this->generateAppConfig();
		$this->assertEquals(false, $config->hasKey('inexistant-app', 'inexistant-key'));
	}

	public function testHasKeyOnMistypedAsLazyReturnsFalse(): void {
		$config = $this->generateAppConfig();
		$this->assertSame(false, $config->hasKey('non-sensitive-app', 'non-lazy-key', true));
	}

	public function testHasKeyOnMistypeAsNonLazyReturnsFalse(): void {
		$config = $this->generateAppConfig();
		$this->assertSame(false, $config->hasKey('non-sensitive-app', 'lazy-key', false));
	}

	public function testHasKeyOnMistypeAsNonLazyReturnsTrueWithLazyArgumentIsNull(): void {
		$config = $this->generateAppConfig();
		$this->assertSame(true, $config->hasKey('non-sensitive-app', 'lazy-key', null));
	}

	/**
	 * @dataProvider providerGetKeys
	 */
	public function testIsSensitive(
		string $appId, string $configKey, string $configValue, int $type, bool $lazy, bool $sensitive,
	): void {
		$config = $this->generateAppConfig();
		$this->assertEquals($sensitive, $config->isSensitive($appId, $configKey, $lazy));
	}

	public function testIsSensitiveOnNonExistentKeyThrowsException(): void {
		$config = $this->generateAppConfig();
		$this->expectException(AppConfigUnknownKeyException::class);
		$config->isSensitive(array_keys(self::$baseStruct)[0], 'inexistant-key');
	}

	public function testIsSensitiveOnUnknownAppThrowsException(): void {
		$config = $this->generateAppConfig();
		$this->expectException(AppConfigUnknownKeyException::class);
		$config->isSensitive('unknown-app', 'inexistant-key');
	}

	public function testIsSensitiveOnSensitiveMistypedAsLazy(): void {
		$config = $this->generateAppConfig();
		$this->assertSame(true, $config->isSensitive('sensitive-app', 'non-lazy-key', true));
	}

	public function testIsSensitiveOnNonSensitiveMistypedAsLazy(): void {
		$config = $this->generateAppConfig();
		$this->assertSame(false, $config->isSensitive('non-sensitive-app', 'non-lazy-key', true));
	}

	public function testIsSensitiveOnSensitiveMistypedAsNonLazyThrowsException(): void {
		$config = $this->generateAppConfig();
		$this->expectException(AppConfigUnknownKeyException::class);
		$config->isSensitive('sensitive-app', 'lazy-key', false);
	}

	public function testIsSensitiveOnNonSensitiveMistypedAsNonLazyThrowsException(): void {
		$config = $this->generateAppConfig();
		$this->expectException(AppConfigUnknownKeyException::class);
		$config->isSensitive('non-sensitive-app', 'lazy-key', false);
	}

	/**
	 * @dataProvider providerGetKeys
	 */
	public function testIsLazy(string $appId, string $configKey, string $configValue, int $type, bool $lazy,
	): void {
		$config = $this->generateAppConfig();
		$this->assertEquals($lazy, $config->isLazy($appId, $configKey));
	}

	public function testIsLazyOnNonExistentKeyThrowsException(): void {
		$config = $this->generateAppConfig();
		$this->expectException(AppConfigUnknownKeyException::class);
		$config->isLazy(array_keys(self::$baseStruct)[0], 'inexistant-key');
	}

	public function testIsLazyOnUnknownAppThrowsException(): void {
		$config = $this->generateAppConfig();
		$this->expectException(AppConfigUnknownKeyException::class);
		$config->isLazy('unknown-app', 'inexistant-key');
	}

	public function testGetAllValues(): void {
		$config = $this->generateAppConfig();
		$this->assertEquals(
			[
				'array' => ['test' => 1],
				'bool' => true,
				'float' => 3.14,
				'int' => 42,
				'mixed' => 'mix',
				'string' => 'value',
			],
			$config->getAllValues('typed')
		);
	}

	public function testGetAllValuesWithEmptyApp(): void {
		$config = $this->generateAppConfig();
		$this->expectException(InvalidArgumentException::class);
		$config->getAllValues('');
	}

	/**
	 * @dataProvider providerGetAppKeys
	 *
	 * @param string $appId
	 * @param array $keys
	 */
	public function testGetAllValuesWithEmptyKey(string $appId, array $keys): void {
		$config = $this->generateAppConfig();
		$this->assertEqualsCanonicalizing($keys, array_keys($config->getAllValues($appId, '')));
	}

	public function testGetAllValuesWithPrefix(): void {
		$config = $this->generateAppConfig();
		$this->assertEqualsCanonicalizing(['prefix1', 'prefix-2'], array_keys($config->getAllValues('prefix-app', 'prefix')));
	}

	public function testSearchValues(): void {
		$config = $this->generateAppConfig();
		$this->assertEqualsCanonicalizing(['testapp' => 'yes', '123456' => 'yes', 'anotherapp' => 'no'], $config->searchValues('enabled'));
	}

	public function testGetValueString(): void {
		$config = $this->generateAppConfig();
		$this->assertSame('value', $config->getValueString('typed', 'string', ''));
	}

	public function testGetValueStringOnUnknownAppReturnsDefault(): void {
		$config = $this->generateAppConfig();
		$this->assertSame('default-1', $config->getValueString('typed-1', 'string', 'default-1'));
	}

	public function testGetValueStringOnNonExistentKeyReturnsDefault(): void {
		$config = $this->generateAppConfig();
		$this->assertSame('default-2', $config->getValueString('typed', 'string-2', 'default-2'));
	}

	public function testGetValueStringOnWrongType(): void {
		$config = $this->generateAppConfig();
		$this->expectException(AppConfigTypeConflictException::class);
		$config->getValueString('typed', 'int');
	}

	public function testGetNonLazyValueStringAsLazy(): void {
		$config = $this->generateAppConfig();
		$this->assertSame('value', $config->getValueString('non-sensitive-app', 'non-lazy-key', 'default', lazy: true));
	}

	public function testGetValueInt(): void {
		$config = $this->generateAppConfig();
		$this->assertSame(42, $config->getValueInt('typed', 'int', 0));
	}

	public function testGetValueIntOnUnknownAppReturnsDefault(): void {
		$config = $this->generateAppConfig();
		$this->assertSame(1, $config->getValueInt('typed-1', 'int', 1));
	}

	public function testGetValueIntOnNonExistentKeyReturnsDefault(): void {
		$config = $this->generateAppConfig();
		$this->assertSame(2, $config->getValueInt('typed', 'int-2', 2));
	}

	public function testGetValueIntOnWrongType(): void {
		$config = $this->generateAppConfig();
		$this->expectException(AppConfigTypeConflictException::class);
		$config->getValueInt('typed', 'float');
	}

	public function testGetValueFloat(): void {
		$config = $this->generateAppConfig();
		$this->assertSame(3.14, $config->getValueFloat('typed', 'float', 0));
	}

	public function testGetValueFloatOnNonUnknownAppReturnsDefault(): void {
		$config = $this->generateAppConfig();
		$this->assertSame(1.11, $config->getValueFloat('typed-1', 'float', 1.11));
	}

	public function testGetValueFloatOnNonExistentKeyReturnsDefault(): void {
		$config = $this->generateAppConfig();
		$this->assertSame(2.22, $config->getValueFloat('typed', 'float-2', 2.22));
	}

	public function testGetValueFloatOnWrongType(): void {
		$config = $this->generateAppConfig();
		$this->expectException(AppConfigTypeConflictException::class);
		$config->getValueFloat('typed', 'bool');
	}

	public function testGetValueBool(): void {
		$config = $this->generateAppConfig();
		$this->assertSame(true, $config->getValueBool('typed', 'bool'));
	}

	public function testGetValueBoolOnUnknownAppReturnsDefault(): void {
		$config = $this->generateAppConfig();
		$this->assertSame(false, $config->getValueBool('typed-1', 'bool', false));
	}

	public function testGetValueBoolOnNonExistentKeyReturnsDefault(): void {
		$config = $this->generateAppConfig();
		$this->assertSame(false, $config->getValueBool('typed', 'bool-2'));
	}

	public function testGetValueBoolOnWrongType(): void {
		$config = $this->generateAppConfig();
		$this->expectException(AppConfigTypeConflictException::class);
		$config->getValueBool('typed', 'array');
	}

	public function testGetValueArray(): void {
		$config = $this->generateAppConfig();
		$this->assertEqualsCanonicalizing(['test' => 1], $config->getValueArray('typed', 'array', []));
	}

	public function testGetValueArrayOnUnknownAppReturnsDefault(): void {
		$config = $this->generateAppConfig();
		$this->assertSame([1], $config->getValueArray('typed-1', 'array', [1]));
	}

	public function testGetValueArrayOnNonExistentKeyReturnsDefault(): void {
		$config = $this->generateAppConfig();
		$this->assertSame([1, 2], $config->getValueArray('typed', 'array-2', [1, 2]));
	}

	public function testGetValueArrayOnWrongType(): void {
		$config = $this->generateAppConfig();
		$this->expectException(AppConfigTypeConflictException::class);
		$config->getValueArray('typed', 'string');
	}


	/**
	 * @return array
	 * @see testGetValueType
	 *
	 * @see testGetValueMixed
	 */
	public static function providerGetValueMixed(): array {
		return [
			// key, value, type
			['mixed', 'mix', IAppConfig::VALUE_MIXED],
			['string', 'value', IAppConfig::VALUE_STRING],
			['int', '42', IAppConfig::VALUE_INT],
			['float', '3.14', IAppConfig::VALUE_FLOAT],
			['bool', '1', IAppConfig::VALUE_BOOL],
			['array', '{"test": 1}', IAppConfig::VALUE_ARRAY],
		];
	}

	/**
	 * @dataProvider providerGetValueMixed
	 *
	 * @param string $key
	 * @param string $value
	 */
	public function testGetValueMixed(string $key, string $value): void {
		$config = $this->generateAppConfig();
		$this->assertSame($value, $config->getValueMixed('typed', $key));
	}

	/**
	 * @dataProvider providerGetValueMixed
	 *
	 * @param string $key
	 * @param string $value
	 * @param int $type
	 */
	public function testGetValueType(string $key, string $value, int $type): void {
		$config = $this->generateAppConfig();
		$this->assertSame($type, $config->getValueType('typed', $key));
	}

	public function testGetValueTypeOnUnknownApp(): void {
		$config = $this->generateAppConfig();
		$this->expectException(AppConfigUnknownKeyException::class);
		$config->getValueType('typed-1', 'string');
	}

	public function testGetValueTypeOnNonExistentKey(): void {
		$config = $this->generateAppConfig();
		$this->expectException(AppConfigUnknownKeyException::class);
		$config->getValueType('typed', 'string-2');
	}

	public function testSetValueString(): void {
		$config = $this->generateAppConfig();
		$config->setValueString('feed', 'string', 'value-1');
		$this->assertSame('value-1', $config->getValueString('feed', 'string', ''));
	}

	public function testSetValueStringCache(): void {
		$config = $this->generateAppConfig();
		$config->setValueString('feed', 'string', 'value-1');
		$status = $config->statusCache();
		$this->assertSame('value-1', $status['fastCache']['feed']['string']);
	}

	public function testSetValueStringDatabase(): void {
		$config = $this->generateAppConfig();
		$config->setValueString('feed', 'string', 'value-1');
		$config->clearCache();
		$this->assertSame('value-1', $config->getValueString('feed', 'string', ''));
	}

	public function testSetValueStringIsUpdated(): void {
		$config = $this->generateAppConfig();
		$config->setValueString('feed', 'string', 'value-1');
		$this->assertSame(true, $config->setValueString('feed', 'string', 'value-2'));
	}

	public function testSetValueStringIsNotUpdated(): void {
		$config = $this->generateAppConfig();
		$config->setValueString('feed', 'string', 'value-1');
		$this->assertSame(false, $config->setValueString('feed', 'string', 'value-1'));
	}

	public function testSetValueStringIsUpdatedCache(): void {
		$config = $this->generateAppConfig();
		$config->setValueString('feed', 'string', 'value-1');
		$config->setValueString('feed', 'string', 'value-2');
		$status = $config->statusCache();
		$this->assertSame('value-2', $status['fastCache']['feed']['string']);
	}

	public function testSetValueStringIsUpdatedDatabase(): void {
		$config = $this->generateAppConfig();
		$config->setValueString('feed', 'string', 'value-1');
		$config->setValueString('feed', 'string', 'value-2');
		$config->clearCache();
		$this->assertSame('value-2', $config->getValueString('feed', 'string', ''));
	}

	public function testSetValueInt(): void {
		$config = $this->generateAppConfig();
		$config->setValueInt('feed', 'int', 42);
		$this->assertSame(42, $config->getValueInt('feed', 'int', 0));
	}

	public function testSetValueIntCache(): void {
		$config = $this->generateAppConfig();
		$config->setValueInt('feed', 'int', 42);
		$status = $config->statusCache();
		$this->assertSame('42', $status['fastCache']['feed']['int']);
	}

	public function testSetValueIntDatabase(): void {
		$config = $this->generateAppConfig();
		$config->setValueInt('feed', 'int', 42);
		$config->clearCache();
		$this->assertSame(42, $config->getValueInt('feed', 'int', 0));
	}

	public function testSetValueIntIsUpdated(): void {
		$config = $this->generateAppConfig();
		$config->setValueInt('feed', 'int', 42);
		$this->assertSame(true, $config->setValueInt('feed', 'int', 17));
	}

	public function testSetValueIntIsNotUpdated(): void {
		$config = $this->generateAppConfig();
		$config->setValueInt('feed', 'int', 42);
		$this->assertSame(false, $config->setValueInt('feed', 'int', 42));
	}

	public function testSetValueIntIsUpdatedCache(): void {
		$config = $this->generateAppConfig();
		$config->setValueInt('feed', 'int', 42);
		$config->setValueInt('feed', 'int', 17);
		$status = $config->statusCache();
		$this->assertSame('17', $status['fastCache']['feed']['int']);
	}

	public function testSetValueIntIsUpdatedDatabase(): void {
		$config = $this->generateAppConfig();
		$config->setValueInt('feed', 'int', 42);
		$config->setValueInt('feed', 'int', 17);
		$config->clearCache();
		$this->assertSame(17, $config->getValueInt('feed', 'int', 0));
	}

	public function testSetValueFloat(): void {
		$config = $this->generateAppConfig();
		$config->setValueFloat('feed', 'float', 3.14);
		$this->assertSame(3.14, $config->getValueFloat('feed', 'float', 0));
	}

	public function testSetValueFloatCache(): void {
		$config = $this->generateAppConfig();
		$config->setValueFloat('feed', 'float', 3.14);
		$status = $config->statusCache();
		$this->assertSame('3.14', $status['fastCache']['feed']['float']);
	}

	public function testSetValueFloatDatabase(): void {
		$config = $this->generateAppConfig();
		$config->setValueFloat('feed', 'float', 3.14);
		$config->clearCache();
		$this->assertSame(3.14, $config->getValueFloat('feed', 'float', 0));
	}

	public function testSetValueFloatIsUpdated(): void {
		$config = $this->generateAppConfig();
		$config->setValueFloat('feed', 'float', 3.14);
		$this->assertSame(true, $config->setValueFloat('feed', 'float', 1.23));
	}

	public function testSetValueFloatIsNotUpdated(): void {
		$config = $this->generateAppConfig();
		$config->setValueFloat('feed', 'float', 3.14);
		$this->assertSame(false, $config->setValueFloat('feed', 'float', 3.14));
	}

	public function testSetValueFloatIsUpdatedCache(): void {
		$config = $this->generateAppConfig();
		$config->setValueFloat('feed', 'float', 3.14);
		$config->setValueFloat('feed', 'float', 1.23);
		$status = $config->statusCache();
		$this->assertSame('1.23', $status['fastCache']['feed']['float']);
	}

	public function testSetValueFloatIsUpdatedDatabase(): void {
		$config = $this->generateAppConfig();
		$config->setValueFloat('feed', 'float', 3.14);
		$config->setValueFloat('feed', 'float', 1.23);
		$config->clearCache();
		$this->assertSame(1.23, $config->getValueFloat('feed', 'float', 0));
	}

	public function testSetValueBool(): void {
		$config = $this->generateAppConfig();
		$config->setValueBool('feed', 'bool', true);
		$this->assertSame(true, $config->getValueBool('feed', 'bool', false));
	}

	public function testSetValueBoolCache(): void {
		$config = $this->generateAppConfig();
		$config->setValueBool('feed', 'bool', true);
		$status = $config->statusCache();
		$this->assertSame('1', $status['fastCache']['feed']['bool']);
	}

	public function testSetValueBoolDatabase(): void {
		$config = $this->generateAppConfig();
		$config->setValueBool('feed', 'bool', true);
		$config->clearCache();
		$this->assertSame(true, $config->getValueBool('feed', 'bool', false));
	}

	public function testSetValueBoolIsUpdated(): void {
		$config = $this->generateAppConfig();
		$config->setValueBool('feed', 'bool', true);
		$this->assertSame(true, $config->setValueBool('feed', 'bool', false));
	}

	public function testSetValueBoolIsNotUpdated(): void {
		$config = $this->generateAppConfig();
		$config->setValueBool('feed', 'bool', true);
		$this->assertSame(false, $config->setValueBool('feed', 'bool', true));
	}

	public function testSetValueBoolIsUpdatedCache(): void {
		$config = $this->generateAppConfig();
		$config->setValueBool('feed', 'bool', true);
		$config->setValueBool('feed', 'bool', false);
		$status = $config->statusCache();
		$this->assertSame('0', $status['fastCache']['feed']['bool']);
	}

	public function testSetValueBoolIsUpdatedDatabase(): void {
		$config = $this->generateAppConfig();
		$config->setValueBool('feed', 'bool', true);
		$config->setValueBool('feed', 'bool', false);
		$config->clearCache();
		$this->assertSame(false, $config->getValueBool('feed', 'bool', true));
	}


	public function testSetValueArray(): void {
		$config = $this->generateAppConfig();
		$config->setValueArray('feed', 'array', ['test' => 1]);
		$this->assertSame(['test' => 1], $config->getValueArray('feed', 'array', []));
	}

	public function testSetValueArrayCache(): void {
		$config = $this->generateAppConfig();
		$config->setValueArray('feed', 'array', ['test' => 1]);
		$status = $config->statusCache();
		$this->assertSame('{"test":1}', $status['fastCache']['feed']['array']);
	}

	public function testSetValueArrayDatabase(): void {
		$config = $this->generateAppConfig();
		$config->setValueArray('feed', 'array', ['test' => 1]);
		$config->clearCache();
		$this->assertSame(['test' => 1], $config->getValueArray('feed', 'array', []));
	}

	public function testSetValueArrayIsUpdated(): void {
		$config = $this->generateAppConfig();
		$config->setValueArray('feed', 'array', ['test' => 1]);
		$this->assertSame(true, $config->setValueArray('feed', 'array', ['test' => 2]));
	}

	public function testSetValueArrayIsNotUpdated(): void {
		$config = $this->generateAppConfig();
		$config->setValueArray('feed', 'array', ['test' => 1]);
		$this->assertSame(false, $config->setValueArray('feed', 'array', ['test' => 1]));
	}

	public function testSetValueArrayIsUpdatedCache(): void {
		$config = $this->generateAppConfig();
		$config->setValueArray('feed', 'array', ['test' => 1]);
		$config->setValueArray('feed', 'array', ['test' => 2]);
		$status = $config->statusCache();
		$this->assertSame('{"test":2}', $status['fastCache']['feed']['array']);
	}

	public function testSetValueArrayIsUpdatedDatabase(): void {
		$config = $this->generateAppConfig();
		$config->setValueArray('feed', 'array', ['test' => 1]);
		$config->setValueArray('feed', 'array', ['test' => 2]);
		$config->clearCache();
		$this->assertSame(['test' => 2], $config->getValueArray('feed', 'array', []));
	}

	public function testSetLazyValueString(): void {
		$config = $this->generateAppConfig();
		$config->setValueString('feed', 'string', 'value-1', true);
		$this->assertSame('value-1', $config->getValueString('feed', 'string', '', true));
	}

	public function testSetLazyValueStringCache(): void {
		$config = $this->generateAppConfig();
		$config->setValueString('feed', 'string', 'value-1', true);
		$status = $config->statusCache();
		$this->assertSame('value-1', $status['lazyCache']['feed']['string']);
	}

	public function testSetLazyValueStringDatabase(): void {
		$config = $this->generateAppConfig();
		$config->setValueString('feed', 'string', 'value-1', true);
		$config->clearCache();
		$this->assertSame('value-1', $config->getValueString('feed', 'string', '', true));
	}

	public function testSetLazyValueStringAsNonLazy(): void {
		$config = $this->generateAppConfig();
		$config->setValueString('feed', 'string', 'value-1', true);
		$config->setValueString('feed', 'string', 'value-1', false);
		$this->assertSame('value-1', $config->getValueString('feed', 'string', ''));
	}

	public function testSetNonLazyValueStringAsLazy(): void {
		$config = $this->generateAppConfig();
		$config->setValueString('feed', 'string', 'value-1', false);
		$config->setValueString('feed', 'string', 'value-1', true);
		$this->assertSame('value-1', $config->getValueString('feed', 'string', '', true));
	}

	public function testSetSensitiveValueString(): void {
		$config = $this->generateAppConfig();
		$config->setValueString('feed', 'string', 'value-1', sensitive: true);
		$this->assertSame('value-1', $config->getValueString('feed', 'string', ''));
	}

	public function testSetSensitiveValueStringCache(): void {
		$config = $this->generateAppConfig();
		$config->setValueString('feed', 'string', 'value-1', sensitive: true);
		$status = $config->statusCache();
		$this->assertStringStartsWith(self::invokePrivate(AppConfig::class, 'ENCRYPTION_PREFIX'), $status['fastCache']['feed']['string']);
	}

	public function testSetSensitiveValueStringDatabase(): void {
		$config = $this->generateAppConfig();
		$config->setValueString('feed', 'string', 'value-1', sensitive: true);
		$config->clearCache();
		$this->assertSame('value-1', $config->getValueString('feed', 'string', ''));
	}

	public function testSetNonSensitiveValueStringAsSensitive(): void {
		$config = $this->generateAppConfig();
		$config->setValueString('feed', 'string', 'value-1', sensitive: false);
		$config->setValueString('feed', 'string', 'value-1', sensitive: true);
		$this->assertSame(true, $config->isSensitive('feed', 'string'));

		$this->assertConfigValueNotEquals('feed', 'string', 'value-1');
		$this->assertConfigValueNotEquals('feed', 'string', 'value-2');
	}

	public function testSetSensitiveValueStringAsNonSensitiveStaysSensitive(): void {
		$config = $this->generateAppConfig();
		$config->setValueString('feed', 'string', 'value-1', sensitive: true);
		$config->setValueString('feed', 'string', 'value-2', sensitive: false);
		$this->assertSame(true, $config->isSensitive('feed', 'string'));

		$this->assertConfigValueNotEquals('feed', 'string', 'value-1');
		$this->assertConfigValueNotEquals('feed', 'string', 'value-2');
	}

	public function testSetSensitiveValueStringAsNonSensitiveAreStillUpdated(): void {
		$config = $this->generateAppConfig();
		$config->setValueString('feed', 'string', 'value-1', sensitive: true);
		$config->setValueString('feed', 'string', 'value-2', sensitive: false);
		$this->assertSame('value-2', $config->getValueString('feed', 'string', ''));

		$this->assertConfigValueNotEquals('feed', 'string', 'value-1');
		$this->assertConfigValueNotEquals('feed', 'string', 'value-2');
	}

	public function testSetLazyValueInt(): void {
		$config = $this->generateAppConfig();
		$config->setValueInt('feed', 'int', 42, true);
		$this->assertSame(42, $config->getValueInt('feed', 'int', 0, true));
	}

	public function testSetLazyValueIntCache(): void {
		$config = $this->generateAppConfig();
		$config->setValueInt('feed', 'int', 42, true);
		$status = $config->statusCache();
		$this->assertSame('42', $status['lazyCache']['feed']['int']);
	}

	public function testSetLazyValueIntDatabase(): void {
		$config = $this->generateAppConfig();
		$config->setValueInt('feed', 'int', 42, true);
		$config->clearCache();
		$this->assertSame(42, $config->getValueInt('feed', 'int', 0, true));
	}

	public function testSetLazyValueIntAsNonLazy(): void {
		$config = $this->generateAppConfig();
		$config->setValueInt('feed', 'int', 42, true);
		$config->setValueInt('feed', 'int', 42, false);
		$this->assertSame(42, $config->getValueInt('feed', 'int', 0));
	}

	public function testSetNonLazyValueIntAsLazy(): void {
		$config = $this->generateAppConfig();
		$config->setValueInt('feed', 'int', 42, false);
		$config->setValueInt('feed', 'int', 42, true);
		$this->assertSame(42, $config->getValueInt('feed', 'int', 0, true));
	}

	public function testSetSensitiveValueInt(): void {
		$config = $this->generateAppConfig();
		$config->setValueInt('feed', 'int', 42, sensitive: true);
		$this->assertSame(42, $config->getValueInt('feed', 'int', 0));
	}

	public function testSetSensitiveValueIntCache(): void {
		$config = $this->generateAppConfig();
		$config->setValueInt('feed', 'int', 42, sensitive: true);
		$status = $config->statusCache();
		$this->assertStringStartsWith(self::invokePrivate(AppConfig::class, 'ENCRYPTION_PREFIX'), $status['fastCache']['feed']['int']);
	}

	public function testSetSensitiveValueIntDatabase(): void {
		$config = $this->generateAppConfig();
		$config->setValueInt('feed', 'int', 42, sensitive: true);
		$config->clearCache();
		$this->assertSame(42, $config->getValueInt('feed', 'int', 0));
	}

	public function testSetNonSensitiveValueIntAsSensitive(): void {
		$config = $this->generateAppConfig();
		$config->setValueInt('feed', 'int', 42);
		$config->setValueInt('feed', 'int', 42, sensitive: true);
		$this->assertSame(true, $config->isSensitive('feed', 'int'));
	}

	public function testSetSensitiveValueIntAsNonSensitiveStaysSensitive(): void {
		$config = $this->generateAppConfig();
		$config->setValueInt('feed', 'int', 42, sensitive: true);
		$config->setValueInt('feed', 'int', 17);
		$this->assertSame(true, $config->isSensitive('feed', 'int'));
	}

	public function testSetSensitiveValueIntAsNonSensitiveAreStillUpdated(): void {
		$config = $this->generateAppConfig();
		$config->setValueInt('feed', 'int', 42, sensitive: true);
		$config->setValueInt('feed', 'int', 17);
		$this->assertSame(17, $config->getValueInt('feed', 'int', 0));
	}

	public function testSetLazyValueFloat(): void {
		$config = $this->generateAppConfig();
		$config->setValueFloat('feed', 'float', 3.14, true);
		$this->assertSame(3.14, $config->getValueFloat('feed', 'float', 0, true));
	}

	public function testSetLazyValueFloatCache(): void {
		$config = $this->generateAppConfig();
		$config->setValueFloat('feed', 'float', 3.14, true);
		$status = $config->statusCache();
		$this->assertSame('3.14', $status['lazyCache']['feed']['float']);
	}

	public function testSetLazyValueFloatDatabase(): void {
		$config = $this->generateAppConfig();
		$config->setValueFloat('feed', 'float', 3.14, true);
		$config->clearCache();
		$this->assertSame(3.14, $config->getValueFloat('feed', 'float', 0, true));
	}

	public function testSetLazyValueFloatAsNonLazy(): void {
		$config = $this->generateAppConfig();
		$config->setValueFloat('feed', 'float', 3.14, true);
		$config->setValueFloat('feed', 'float', 3.14, false);
		$this->assertSame(3.14, $config->getValueFloat('feed', 'float', 0));
	}

	public function testSetNonLazyValueFloatAsLazy(): void {
		$config = $this->generateAppConfig();
		$config->setValueFloat('feed', 'float', 3.14, false);
		$config->setValueFloat('feed', 'float', 3.14, true);
		$this->assertSame(3.14, $config->getValueFloat('feed', 'float', 0, true));
	}

	public function testSetSensitiveValueFloat(): void {
		$config = $this->generateAppConfig();
		$config->setValueFloat('feed', 'float', 3.14, sensitive: true);
		$this->assertSame(3.14, $config->getValueFloat('feed', 'float', 0));
	}

	public function testSetSensitiveValueFloatCache(): void {
		$config = $this->generateAppConfig();
		$config->setValueFloat('feed', 'float', 3.14, sensitive: true);
		$status = $config->statusCache();
		$this->assertStringStartsWith(self::invokePrivate(AppConfig::class, 'ENCRYPTION_PREFIX'), $status['fastCache']['feed']['float']);
	}

	public function testSetSensitiveValueFloatDatabase(): void {
		$config = $this->generateAppConfig();
		$config->setValueFloat('feed', 'float', 3.14, sensitive: true);
		$config->clearCache();
		$this->assertSame(3.14, $config->getValueFloat('feed', 'float', 0));
	}

	public function testSetNonSensitiveValueFloatAsSensitive(): void {
		$config = $this->generateAppConfig();
		$config->setValueFloat('feed', 'float', 3.14);
		$config->setValueFloat('feed', 'float', 3.14, sensitive: true);
		$this->assertSame(true, $config->isSensitive('feed', 'float'));
	}

	public function testSetSensitiveValueFloatAsNonSensitiveStaysSensitive(): void {
		$config = $this->generateAppConfig();
		$config->setValueFloat('feed', 'float', 3.14, sensitive: true);
		$config->setValueFloat('feed', 'float', 1.23);
		$this->assertSame(true, $config->isSensitive('feed', 'float'));
	}

	public function testSetSensitiveValueFloatAsNonSensitiveAreStillUpdated(): void {
		$config = $this->generateAppConfig();
		$config->setValueFloat('feed', 'float', 3.14, sensitive: true);
		$config->setValueFloat('feed', 'float', 1.23);
		$this->assertSame(1.23, $config->getValueFloat('feed', 'float', 0));
	}

	public function testSetLazyValueBool(): void {
		$config = $this->generateAppConfig();
		$config->setValueBool('feed', 'bool', true, true);
		$this->assertSame(true, $config->getValueBool('feed', 'bool', false, true));
	}

	public function testSetLazyValueBoolCache(): void {
		$config = $this->generateAppConfig();
		$config->setValueBool('feed', 'bool', true, true);
		$status = $config->statusCache();
		$this->assertSame('1', $status['lazyCache']['feed']['bool']);
	}

	public function testSetLazyValueBoolDatabase(): void {
		$config = $this->generateAppConfig();
		$config->setValueBool('feed', 'bool', true, true);
		$config->clearCache();
		$this->assertSame(true, $config->getValueBool('feed', 'bool', false, true));
	}

	public function testSetLazyValueBoolAsNonLazy(): void {
		$config = $this->generateAppConfig();
		$config->setValueBool('feed', 'bool', true, true);
		$config->setValueBool('feed', 'bool', true, false);
		$this->assertSame(true, $config->getValueBool('feed', 'bool', false));
	}

	public function testSetNonLazyValueBoolAsLazy(): void {
		$config = $this->generateAppConfig();
		$config->setValueBool('feed', 'bool', true, false);
		$config->setValueBool('feed', 'bool', true, true);
		$this->assertSame(true, $config->getValueBool('feed', 'bool', false, true));
	}

	public function testSetLazyValueArray(): void {
		$config = $this->generateAppConfig();
		$config->setValueArray('feed', 'array', ['test' => 1], true);
		$this->assertSame(['test' => 1], $config->getValueArray('feed', 'array', [], true));
	}

	public function testSetLazyValueArrayCache(): void {
		$config = $this->generateAppConfig();
		$config->setValueArray('feed', 'array', ['test' => 1], true);
		$status = $config->statusCache();
		$this->assertSame('{"test":1}', $status['lazyCache']['feed']['array']);
	}

	public function testSetLazyValueArrayDatabase(): void {
		$config = $this->generateAppConfig();
		$config->setValueArray('feed', 'array', ['test' => 1], true);
		$config->clearCache();
		$this->assertSame(['test' => 1], $config->getValueArray('feed', 'array', [], true));
	}

	public function testSetLazyValueArrayAsNonLazy(): void {
		$config = $this->generateAppConfig();
		$config->setValueArray('feed', 'array', ['test' => 1], true);
		$config->setValueArray('feed', 'array', ['test' => 1], false);
		$this->assertSame(['test' => 1], $config->getValueArray('feed', 'array', []));
	}

	public function testSetNonLazyValueArrayAsLazy(): void {
		$config = $this->generateAppConfig();
		$config->setValueArray('feed', 'array', ['test' => 1], false);
		$config->setValueArray('feed', 'array', ['test' => 1], true);
		$this->assertSame(['test' => 1], $config->getValueArray('feed', 'array', [], true));
	}


	public function testSetSensitiveValueArray(): void {
		$config = $this->generateAppConfig();
		$config->setValueArray('feed', 'array', ['test' => 1], sensitive: true);
		$this->assertEqualsCanonicalizing(['test' => 1], $config->getValueArray('feed', 'array', []));
	}

	public function testSetSensitiveValueArrayCache(): void {
		$config = $this->generateAppConfig();
		$config->setValueArray('feed', 'array', ['test' => 1], sensitive: true);
		$status = $config->statusCache();
		$this->assertStringStartsWith(self::invokePrivate(AppConfig::class, 'ENCRYPTION_PREFIX'), $status['fastCache']['feed']['array']);
	}

	public function testSetSensitiveValueArrayDatabase(): void {
		$config = $this->generateAppConfig();
		$config->setValueArray('feed', 'array', ['test' => 1], sensitive: true);
		$config->clearCache();
		$this->assertEqualsCanonicalizing(['test' => 1], $config->getValueArray('feed', 'array', []));
	}

	public function testSetNonSensitiveValueArrayAsSensitive(): void {
		$config = $this->generateAppConfig();
		$config->setValueArray('feed', 'array', ['test' => 1]);
		$config->setValueArray('feed', 'array', ['test' => 1], sensitive: true);
		$this->assertSame(true, $config->isSensitive('feed', 'array'));
	}

	public function testSetSensitiveValueArrayAsNonSensitiveStaysSensitive(): void {
		$config = $this->generateAppConfig();
		$config->setValueArray('feed', 'array', ['test' => 1], sensitive: true);
		$config->setValueArray('feed', 'array', ['test' => 2]);
		$this->assertSame(true, $config->isSensitive('feed', 'array'));
	}

	public function testSetSensitiveValueArrayAsNonSensitiveAreStillUpdated(): void {
		$config = $this->generateAppConfig();
		$config->setValueArray('feed', 'array', ['test' => 1], sensitive: true);
		$config->setValueArray('feed', 'array', ['test' => 2]);
		$this->assertEqualsCanonicalizing(['test' => 2], $config->getValueArray('feed', 'array', []));
	}

	public function testUpdateNotSensitiveToSensitive(): void {
		$config = $this->generateAppConfig();
		$config->updateSensitive('non-sensitive-app', 'lazy-key', true);
		$this->assertSame(true, $config->isSensitive('non-sensitive-app', 'lazy-key', true));
	}

	public function testUpdateSensitiveToNotSensitive(): void {
		$config = $this->generateAppConfig();
		$config->updateSensitive('sensitive-app', 'lazy-key', false);
		$this->assertSame(false, $config->isSensitive('sensitive-app', 'lazy-key', true));
	}

	public function testUpdateSensitiveToSensitiveReturnsFalse(): void {
		$config = $this->generateAppConfig();
		$this->assertSame(false, $config->updateSensitive('sensitive-app', 'lazy-key', true));
	}

	public function testUpdateNotSensitiveToNotSensitiveReturnsFalse(): void {
		$config = $this->generateAppConfig();
		$this->assertSame(false, $config->updateSensitive('non-sensitive-app', 'lazy-key', false));
	}

	public function testUpdateSensitiveOnUnknownKeyReturnsFalse(): void {
		$config = $this->generateAppConfig();
		$this->assertSame(false, $config->updateSensitive('non-sensitive-app', 'unknown-key', true));
	}

	public function testUpdateNotLazyToLazy(): void {
		$config = $this->generateAppConfig();
		$config->updateLazy('non-sensitive-app', 'non-lazy-key', true);
		$this->assertSame(true, $config->isLazy('non-sensitive-app', 'non-lazy-key'));
	}

	public function testUpdateLazyToNotLazy(): void {
		$config = $this->generateAppConfig();
		$config->updateLazy('non-sensitive-app', 'lazy-key', false);
		$this->assertSame(false, $config->isLazy('non-sensitive-app', 'lazy-key'));
	}

	public function testUpdateLazyToLazyReturnsFalse(): void {
		$config = $this->generateAppConfig();
		$this->assertSame(false, $config->updateLazy('non-sensitive-app', 'lazy-key', true));
	}

	public function testUpdateNotLazyToNotLazyReturnsFalse(): void {
		$config = $this->generateAppConfig();
		$this->assertSame(false, $config->updateLazy('non-sensitive-app', 'non-lazy-key', false));
	}

	public function testUpdateLazyOnUnknownKeyReturnsFalse(): void {
		$config = $this->generateAppConfig();
		$this->assertSame(false, $config->updateLazy('non-sensitive-app', 'unknown-key', true));
	}

	public function testGetDetails(): void {
		$config = $this->generateAppConfig();
		$this->assertEquals(
			[
				'app' => 'non-sensitive-app',
				'key' => 'lazy-key',
				'value' => 'value',
				'type' => 4,
				'lazy' => true,
				'typeString' => 'string',
				'sensitive' => false,
			],
			$config->getDetails('non-sensitive-app', 'lazy-key')
		);
	}

	public function testGetDetailsSensitive(): void {
		$config = $this->generateAppConfig();
		$this->assertEquals(
			[
				'app' => 'sensitive-app',
				'key' => 'lazy-key',
				'value' => 'value',
				'type' => 4,
				'lazy' => true,
				'typeString' => 'string',
				'sensitive' => true,
			],
			$config->getDetails('sensitive-app', 'lazy-key')
		);
	}

	public function testGetDetailsInt(): void {
		$config = $this->generateAppConfig();
		$this->assertEquals(
			[
				'app' => 'typed',
				'key' => 'int',
				'value' => '42',
				'type' => 8,
				'lazy' => false,
				'typeString' => 'integer',
				'sensitive' => false
			],
			$config->getDetails('typed', 'int')
		);
	}

	public function testGetDetailsFloat(): void {
		$config = $this->generateAppConfig();
		$this->assertEquals(
			[
				'app' => 'typed',
				'key' => 'float',
				'value' => '3.14',
				'type' => 16,
				'lazy' => false,
				'typeString' => 'float',
				'sensitive' => false
			],
			$config->getDetails('typed', 'float')
		);
	}

	public function testGetDetailsBool(): void {
		$config = $this->generateAppConfig();
		$this->assertEquals(
			[
				'app' => 'typed',
				'key' => 'bool',
				'value' => '1',
				'type' => 32,
				'lazy' => false,
				'typeString' => 'boolean',
				'sensitive' => false
			],
			$config->getDetails('typed', 'bool')
		);
	}

	public function testGetDetailsArray(): void {
		$config = $this->generateAppConfig();
		$this->assertEquals(
			[
				'app' => 'typed',
				'key' => 'array',
				'value' => '{"test": 1}',
				'type' => 64,
				'lazy' => false,
				'typeString' => 'array',
				'sensitive' => false
			],
			$config->getDetails('typed', 'array')
		);
	}

	public function testDeleteKey(): void {
		$config = $this->generateAppConfig();
		$config->deleteKey('anotherapp', 'key');
		$this->assertSame('default', $config->getValueString('anotherapp', 'key', 'default'));
	}

	public function testDeleteKeyCache(): void {
		$config = $this->generateAppConfig();
		$config->deleteKey('anotherapp', 'key');
		$status = $config->statusCache();
		$this->assertEqualsCanonicalizing(['enabled' => 'no', 'installed_version' => '3.2.1'], $status['fastCache']['anotherapp']);
	}

	public function testDeleteKeyDatabase(): void {
		$config = $this->generateAppConfig();
		$config->deleteKey('anotherapp', 'key');
		$config->clearCache();
		$this->assertSame('default', $config->getValueString('anotherapp', 'key', 'default'));
	}

	public function testDeleteApp(): void {
		$config = $this->generateAppConfig();
		$config->deleteApp('anotherapp');
		$this->assertSame('default', $config->getValueString('anotherapp', 'key', 'default'));
		$this->assertSame('default', $config->getValueString('anotherapp', 'enabled', 'default'));
	}

	public function testDeleteAppCache(): void {
		$config = $this->generateAppConfig();
		$status = $config->statusCache();
		$this->assertSame(true, isset($status['fastCache']['anotherapp']));
		$config->deleteApp('anotherapp');
		$status = $config->statusCache();
		$this->assertSame(false, isset($status['fastCache']['anotherapp']));
	}

	public function testDeleteAppDatabase(): void {
		$config = $this->generateAppConfig();
		$config->deleteApp('anotherapp');
		$config->clearCache();
		$this->assertSame('default', $config->getValueString('anotherapp', 'key', 'default'));
		$this->assertSame('default', $config->getValueString('anotherapp', 'enabled', 'default'));
	}

	public function testClearCache(): void {
		$config = $this->generateAppConfig();
		$config->setValueString('feed', 'string', '123454');
		$config->clearCache();
		$status = $config->statusCache();
		$this->assertSame([], $status['fastCache']);
	}

	public function testSensitiveValuesAreEncrypted(): void {
		$key = self::getUniqueID('secret');

		$appConfig = $this->generateAppConfig();
		$secret = md5((string)time());
		$appConfig->setValueString('testapp', $key, $secret, sensitive: true);

		$this->assertConfigValueNotEquals('testapp', $key, $secret);

		// Can get in same run
		$actualSecret = $appConfig->getValueString('testapp', $key);
		$this->assertEquals($secret, $actualSecret);

		// Can get freshly decrypted from DB
		$newAppConfig = $this->generateAppConfig();
		$actualSecret = $newAppConfig->getValueString('testapp', $key);
		$this->assertEquals($secret, $actualSecret);
	}

	public function testMigratingNonSensitiveValueToSensitiveWithSetValue(): void {
		$key = self::getUniqueID('secret');
		$appConfig = $this->generateAppConfig();
		$secret = sha1((string)time());

		// Unencrypted
		$appConfig->setValueString('testapp', $key, $secret);
		$this->assertConfigKey('testapp', $key, $secret);

		// Can get freshly decrypted from DB
		$newAppConfig = $this->generateAppConfig();
		$actualSecret = $newAppConfig->getValueString('testapp', $key);
		$this->assertEquals($secret, $actualSecret);

		// Encrypting on change
		$appConfig->setValueString('testapp', $key, $secret, sensitive: true);
		$this->assertConfigValueNotEquals('testapp', $key, $secret);

		// Can get in same run
		$actualSecret = $appConfig->getValueString('testapp', $key);
		$this->assertEquals($secret, $actualSecret);

		// Can get freshly decrypted from DB
		$newAppConfig = $this->generateAppConfig();
		$actualSecret = $newAppConfig->getValueString('testapp', $key);
		$this->assertEquals($secret, $actualSecret);
	}

	public function testUpdateSensitiveValueToNonSensitiveWithUpdateSensitive(): void {
		$key = self::getUniqueID('secret');
		$appConfig = $this->generateAppConfig();
		$secret = sha1((string)time());

		// Encrypted
		$appConfig->setValueString('testapp', $key, $secret, sensitive: true);
		$this->assertConfigValueNotEquals('testapp', $key, $secret);

		// Migrate to non-sensitive / non-encrypted
		$appConfig->updateSensitive('testapp', $key, false);
		$this->assertConfigKey('testapp', $key, $secret);
	}

	public function testUpdateNonSensitiveValueToSensitiveWithUpdateSensitive(): void {
		$key = self::getUniqueID('secret');
		$appConfig = $this->generateAppConfig();
		$secret = sha1((string)time());

		// Unencrypted
		$appConfig->setValueString('testapp', $key, $secret);
		$this->assertConfigKey('testapp', $key, $secret);

		// Migrate to sensitive / encrypted
		$appConfig->updateSensitive('testapp', $key, true);
		$this->assertConfigValueNotEquals('testapp', $key, $secret);
	}

	protected function loadConfigValueFromDatabase(string $app, string $key): string|false {
		$sql = $this->connection->getQueryBuilder();
		$sql->select('configvalue')
			->from('appconfig')
			->where($sql->expr()->eq('appid', $sql->createParameter('appid')))
			->andWhere($sql->expr()->eq('configkey', $sql->createParameter('configkey')))
			->setParameter('appid', $app)
			->setParameter('configkey', $key);
		$query = $sql->executeQuery();
		$actual = $query->fetchOne();
		$query->closeCursor();

		return $actual;
	}

	protected function assertConfigKey(string $app, string $key, string|false $expected): void {
		$this->assertEquals($expected, $this->loadConfigValueFromDatabase($app, $key));
	}

	protected function assertConfigValueNotEquals(string $app, string $key, string|false $expected): void {
		$this->assertNotEquals($expected, $this->loadConfigValueFromDatabase($app, $key));
	}
}
