<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace lib;

use NCU\Config\Exceptions\TypeConflictException;
use NCU\Config\Exceptions\UnknownKeyException;
use NCU\Config\IUserPreferences;
use NCU\Config\ValueType;
use OC\Config\UserPreferences;
use OCP\IDBConnection;
use OCP\Security\ICrypto;
use Psr\Log\LoggerInterface;
use Test\TestCase;

/**
 * Class UserPreferencesTest
 *
 * @group DB
 *
 * @package Test
 */
class UserPreferencesTest extends TestCase {
	protected IDBConnection $connection;
	private LoggerInterface $logger;
	private ICrypto $crypto;
	private array $originalPreferences;

	/**
	 * @var array<string, array<string, array<array<string, string, int, bool, bool>>> [userId => [appId => prefKey, prefValue, valueType, lazy, sensitive]]]
	 */
	private array $basePreferences =
		[
			'user1' =>
				[
					'app1' => [
						'key1' => ['key1', 'value1'],
						'key22' => ['key22', '31'],
						'fast_string' => ['fast_string', 'f_value', ValueType::STRING],
						'lazy_string' => ['lazy_string', 'l_value', ValueType::STRING, true],
						'fast_string_sensitive' => [
							'fast_string_sensitive', 'fs_value', ValueType::STRING, false, UserPreferences::FLAG_SENSITIVE
						],
						'lazy_string_sensitive' => [
							'lazy_string_sensitive', 'ls_value', ValueType::STRING, true, UserPreferences::FLAG_SENSITIVE
						],
						'fast_int' => ['fast_int', 11, ValueType::INT],
						'lazy_int' => ['lazy_int', 12, ValueType::INT, true],
						'fast_int_sensitive' => ['fast_int_sensitive', 2024, ValueType::INT, false, UserPreferences::FLAG_SENSITIVE],
						'lazy_int_sensitive' => ['lazy_int_sensitive', 2048, ValueType::INT, true, UserPreferences::FLAG_SENSITIVE],
						'fast_float' => ['fast_float', 3.14, ValueType::FLOAT],
						'lazy_float' => ['lazy_float', 3.14159, ValueType::FLOAT, true],
						'fast_float_sensitive' => [
							'fast_float_sensitive', 1.41, ValueType::FLOAT, false, UserPreferences::FLAG_SENSITIVE
						],
						'lazy_float_sensitive' => [
							'lazy_float_sensitive', 1.4142, ValueType::FLOAT, true, UserPreferences::FLAG_SENSITIVE
						],
						'fast_array' => ['fast_array', ['year' => 2024], ValueType::ARRAY],
						'lazy_array' => ['lazy_array', ['month' => 'October'], ValueType::ARRAY, true],
						'fast_array_sensitive' => [
							'fast_array_sensitive', ['password' => 'pwd'], ValueType::ARRAY, false, UserPreferences::FLAG_SENSITIVE
						],
						'lazy_array_sensitive' => [
							'lazy_array_sensitive', ['password' => 'qwerty'], ValueType::ARRAY, true, UserPreferences::FLAG_SENSITIVE
						],
						'fast_boolean' => ['fast_boolean', true, ValueType::BOOL],
						'fast_boolean_0' => ['fast_boolean_0', false, ValueType::BOOL],
						'lazy_boolean' => ['lazy_boolean', true, ValueType::BOOL, true],
						'lazy_boolean_0' => ['lazy_boolean_0', false, ValueType::BOOL, true],
					],
					'app2' => [
						'key2' => ['key2', 'value2a', ValueType::STRING, false, 0, true],
						'key3' => ['key3', 'value3', ValueType::STRING, true],
						'key4' => ['key4', 'value4', ValueType::STRING, false, UserPreferences::FLAG_SENSITIVE],
						'key8' => ['key8', 11, ValueType::INT, false, 0, true],
						'key9' => ['key9', 'value9a', ValueType::STRING],
					],
					'app3' => [
						'key1' => ['key1', 'value123'],
						'key3' => ['key3', 'value3'],
						'key8' => ['key8', 12, ValueType::INT, false, UserPreferences::FLAG_SENSITIVE, true],
						'key9' => ['key9', 'value9b', ValueType::STRING, false, UserPreferences::FLAG_SENSITIVE],
						'key10' => ['key10', true, ValueType::BOOL, false, 0, true],
					],
					'only-lazy' => [
						'key1' => ['key1', 'value456', ValueType::STRING, true, 0, true],
						'key2' => ['key2', 'value2c', ValueType::STRING, true, UserPreferences::FLAG_SENSITIVE],
						'key3' => ['key3', 42, ValueType::INT, true],
						'key4' => ['key4', 17.42, ValueType::FLOAT, true],
						'key5' => ['key5', true, ValueType::BOOL, true],
					]
				],
			'user2' =>
				[
					'app1' => [
						'1' => ['1', 'value1'],
						'2' => ['2', 'value2', ValueType::STRING, true, UserPreferences::FLAG_SENSITIVE],
						'3' => ['3', 17, ValueType::INT, true],
						'4' => ['4', 42, ValueType::INT, false, UserPreferences::FLAG_SENSITIVE],
						'5' => ['5', 17.42, ValueType::FLOAT, false],
						'6' => ['6', true, ValueType::BOOL, false],
					],
					'app2' => [
						'key2' => ['key2', 'value2b', ValueType::STRING, false, 0, true],
						'key3' => ['key3', 'value3', ValueType::STRING, true],
						'key4' => ['key4', 'value4', ValueType::STRING, false, UserPreferences::FLAG_SENSITIVE],
						'key8' => ['key8', 12, ValueType::INT, false, 0, true],
					],
					'app3' => [
						'key10' => ['key10', false, ValueType::BOOL, false, 0, true],
					],
					'only-lazy' => [
						'key1' => ['key1', 'value1', ValueType::STRING, true, 0, true]
					]
				],
			'user3' =>
				[
					'app2' => [
						'key2' => ['key2', 'value2c', ValueType::MIXED, false, 0, true],
						'key3' => ['key3', 'value3', ValueType::STRING, true, ],
						'key4' => ['key4', 'value4', ValueType::STRING, false, UserPreferences::FLAG_SENSITIVE],
						'fast_string_sensitive' => [
							'fast_string_sensitive', 'fs_value', ValueType::STRING, false, UserPreferences::FLAG_SENSITIVE
						],
						'lazy_string_sensitive' => [
							'lazy_string_sensitive', 'ls_value', ValueType::STRING, true, UserPreferences::FLAG_SENSITIVE
						],
					],
					'only-lazy' => [
						'key3' => ['key3', 'value3', ValueType::STRING, true]
					]
				],
			'user4' =>
				[
					'app2' => [
						'key1' => ['key1', 'value1'],
						'key2' => ['key2', 'value2A', ValueType::MIXED, false, 0, true],
						'key3' => ['key3', 'value3', ValueType::STRING, true,],
						'key4' => ['key4', 'value4', ValueType::STRING, false, UserPreferences::FLAG_SENSITIVE],
					],
					'app3' => [
						'key10' => ['key10', true, ValueType::BOOL, false, 0, true],
					],
					'only-lazy' => [
						'key1' => ['key1', 123, ValueType::INT, true, 0, true]
					]
				],
			'user5' =>
				[
					'app1' => [
						'key1' => ['key1', 'value1']
					],
					'app2' => [
						'key8' => ['key8', 12, ValueType::INT, false, 0, true]
					],
					'only-lazy' => [
						'key1' => ['key1', 'value1', ValueType::STRING, true, 0, true]
					]
				],

		];

	protected function setUp(): void {
		parent::setUp();

		$this->connection = \OCP\Server::get(IDBConnection::class);
		$this->logger = \OCP\Server::get(LoggerInterface::class);
		$this->crypto = \OCP\Server::get(ICrypto::class);

		// storing current preferences and emptying the data table
		$sql = $this->connection->getQueryBuilder();
		$sql->select('*')
			->from('preferences');
		$result = $sql->executeQuery();
		$this->originalPreferences = $result->fetchAll();
		$result->closeCursor();

		$sql = $this->connection->getQueryBuilder();
		$sql->delete('preferences');
		$sql->executeStatement();

		$sql = $this->connection->getQueryBuilder();
		$sql->insert('preferences')
			->values(
				[
					'userid' => $sql->createParameter('userid'),
					'appid' => $sql->createParameter('appid'),
					'configkey' => $sql->createParameter('configkey'),
					'configvalue' => $sql->createParameter('configvalue'),
					'type' => $sql->createParameter('type'),
					'lazy' => $sql->createParameter('lazy'),
					'flags' => $sql->createParameter('flags'),
					'indexed' => $sql->createParameter('indexed')
				]
			);

		foreach ($this->basePreferences as $userId => $userData) {
			foreach ($userData as $appId => $appData) {
				foreach ($appData as $key => $row) {
					$value = $row[1];
					$type = ($row[2] ?? ValueType::MIXED)->value;

					if ($type === ValueType::ARRAY->value) {
						$value = json_encode($value);
					}

					if ($type === ValueType::BOOL->value && $value === false) {
						$value = '0';
					}

					$flags = $row[4] ?? 0;
					if ((UserPreferences::FLAG_SENSITIVE & $flags) !== 0) {
						$value = self::invokePrivate(UserPreferences::class, 'ENCRYPTION_PREFIX')
								 . $this->crypto->encrypt((string)$value);
					} else {
						$indexed = (($row[5] ?? false) === true) ? $value : '';
					}

					$sql->setParameters(
						[
							'userid' => $userId,
							'appid' => $appId,
							'configkey' => $row[0],
							'configvalue' => $value,
							'type' => $type,
							'lazy' => (($row[3] ?? false) === true) ? 1 : 0,
							'flags' => $flags,
							'indexed' => $indexed ?? ''
						]
					)->executeStatement();
				}
			}
		}
	}

	protected function tearDown(): void {
		$sql = $this->connection->getQueryBuilder();
		$sql->delete('preferences');
		$sql->executeStatement();

		$sql = $this->connection->getQueryBuilder();
		$sql->insert('preferences')
			->values(
				[
					'userid' => $sql->createParameter('userid'),
					'appid' => $sql->createParameter('appid'),
					'configkey' => $sql->createParameter('configkey'),
					'configvalue' => $sql->createParameter('configvalue'),
					'lazy' => $sql->createParameter('lazy'),
					'type' => $sql->createParameter('type'),
				]
			);

		foreach ($this->originalPreferences as $key => $configs) {
			$sql->setParameter('userid', $configs['userid'])
				->setParameter('appid', $configs['appid'])
				->setParameter('configkey', $configs['configkey'])
				->setParameter('configvalue', $configs['configvalue'])
				->setParameter('lazy', ($configs['lazy'] === '1') ? '1' : '0')
				->setParameter('type', $configs['type']);
			$sql->executeStatement();
		}

		parent::tearDown();
	}

	/**
	 * @param array $preLoading preload the 'fast' cache for a list of users)
	 *
	 * @return IUserPreferences
	 */
	private function generateUserPreferences(array $preLoading = []): IUserPreferences {
		$preferences = new \OC\Config\UserPreferences(
			$this->connection,
			$this->logger,
			$this->crypto,
		);
		$msg = ' generateUserPreferences() failed to confirm cache status';

		// confirm cache status
		$status = $preferences->statusCache();
		$this->assertSame([], $status['fastLoaded'], $msg);
		$this->assertSame([], $status['lazyLoaded'], $msg);
		$this->assertSame([], $status['fastCache'], $msg);
		$this->assertSame([], $status['lazyCache'], $msg);
		foreach ($preLoading as $preLoadUser) {
			// simple way to initiate the load of non-lazy preferences values in cache
			$preferences->getValueString($preLoadUser, 'core', 'preload');

			// confirm cache status
			$status = $preferences->statusCache();
			$this->assertSame(true, $status['fastLoaded'][$preLoadUser], $msg);
			$this->assertSame(false, $status['lazyLoaded'][$preLoadUser], $msg);

			$apps = array_values(array_diff(array_keys($this->basePreferences[$preLoadUser]), ['only-lazy']));
			$this->assertEqualsCanonicalizing($apps, array_keys($status['fastCache'][$preLoadUser]), $msg);
			$this->assertSame([], array_keys($status['lazyCache'][$preLoadUser]), $msg);
		}

		return $preferences;
	}

	public function testGetUserIdsEmpty(): void {
		$preferences = $this->generateUserPreferences();
		$this->assertEqualsCanonicalizing(array_keys($this->basePreferences), $preferences->getUserIds());
	}

	public function testGetUserIds(): void {
		$preferences = $this->generateUserPreferences();
		$this->assertEqualsCanonicalizing(['user1', 'user2', 'user5'], $preferences->getUserIds('app1'));
	}

	public function testGetApps(): void {
		$preferences = $this->generateUserPreferences();
		$this->assertEqualsCanonicalizing(
			array_keys($this->basePreferences['user1']), $preferences->getApps('user1')
		);
	}

	public function testGetKeys(): void {
		$preferences = $this->generateUserPreferences(['user1']);
		$this->assertEqualsCanonicalizing(
			array_keys($this->basePreferences['user1']['app1']), $preferences->getKeys('user1', 'app1')
		);
	}

	/**
	 * @return array[]
	 */
	public function providerHasKey(): array {
		return [
			['user1', 'app1', 'key1', false, true],
			['user0', 'app1', 'key1', false, false],
			['user1', 'app1', 'key1', true, false],
			['user1', 'app1', 'key0', false, false],
			['user1', 'app1', 'key0', true, false],
			['user1', 'app1', 'fast_string_sensitive', false, true],
			['user1', 'app1', 'lazy_string_sensitive', true, true],
			['user2', 'only-lazy', 'key1', false, false],
			['user2', 'only-lazy', 'key1', true, true],
		];
	}

	/**
	 * @dataProvider providerHasKey
	 */
	public function testHasKey(string $userId, string $appId, string $key, ?bool $lazy, bool $result): void {
		$preferences = $this->generateUserPreferences();
		$this->assertEquals($result, $preferences->hasKey($userId, $appId, $key, $lazy));
	}

	/**
	 * @return array[]
	 */
	public function providerIsSensitive(): array {
		return [
			['user1', 'app1', 'key1', false, false, false],
			['user0', 'app1', 'key1', false, false, true],
			['user1', 'app1', 'key1', true, false, true],
			['user1', 'app1', 'key1', null, false, false],
			['user1', 'app1', 'key0', false, false, true],
			['user1', 'app1', 'key0', true, false, true],
			['user1', 'app1', 'fast_string_sensitive', false, true, false],
			['user1', 'app1', 'lazy_string_sensitive', true, true, false],
			['user1', 'app1', 'fast_string_sensitive', true, true, true],
			['user1', 'app1', 'lazy_string_sensitive', false, true, true],
			['user1', 'app1', 'lazy_string_sensitive', null, true, false],
			['user2', 'only-lazy', 'key1', false, false, true],
			['user2', 'only-lazy', 'key1', true, false, false],
			['user2', 'only-lazy', 'key1', null, false, false],
		];
	}

	/**
	 * @dataProvider providerIsSensitive
	 */
	public function testIsSensitive(
		string $userId,
		string $appId,
		string $key,
		?bool $lazy,
		bool $result,
		bool $exception,
	): void {
		$preferences = $this->generateUserPreferences();
		if ($exception) {
			$this->expectException(UnknownKeyException::class);
		}

		$this->assertEquals($result, $preferences->isSensitive($userId, $appId, $key, $lazy));
	}

	/**
	 * @return array[]
	 */
	public function providerIsLazy(): array {
		return [
			['user1', 'app1', 'key1', false, false],
			['user0', 'app1', 'key1', false, true],
			['user1', 'app1', 'key0', false, true],
			['user1', 'app1', 'key0', false, true],
			['user1', 'app1', 'fast_string_sensitive', false, false],
			['user1', 'app1', 'lazy_string_sensitive', true, false],
			['user2', 'only-lazy', 'key1', true, false],
		];
	}

	/**
	 * @dataProvider providerIsLazy
	 */
	public function testIsLazy(
		string $userId,
		string $appId,
		string $key,
		bool $result,
		bool $exception,
	): void {
		$preferences = $this->generateUserPreferences();
		if ($exception) {
			$this->expectException(UnknownKeyException::class);
		}

		$this->assertEquals($result, $preferences->isLazy($userId, $appId, $key));
	}

	public function providerGetValues(): array {
		return [
			[
				'user1', 'app1', '', true,
				[
					'fast_array' => ['year' => 2024],
					'fast_array_sensitive' => '***REMOVED SENSITIVE VALUE***',
					'fast_boolean' => true,
					'fast_boolean_0' => false,
					'fast_float' => 3.14,
					'fast_float_sensitive' => '***REMOVED SENSITIVE VALUE***',
					'fast_int' => 11,
					'fast_int_sensitive' => '***REMOVED SENSITIVE VALUE***',
					'fast_string' => 'f_value',
					'fast_string_sensitive' => '***REMOVED SENSITIVE VALUE***',
					'key1' => 'value1',
					'key22' => '31',
					'lazy_array' => ['month' => 'October'],
					'lazy_array_sensitive' => '***REMOVED SENSITIVE VALUE***',
					'lazy_boolean' => true,
					'lazy_boolean_0' => false,
					'lazy_float' => 3.14159,
					'lazy_float_sensitive' => '***REMOVED SENSITIVE VALUE***',
					'lazy_int' => 12,
					'lazy_int_sensitive' => '***REMOVED SENSITIVE VALUE***',
					'lazy_string' => 'l_value',
					'lazy_string_sensitive' => '***REMOVED SENSITIVE VALUE***',
				]
			],
			[
				'user1', 'app1', '', false,
				[
					'fast_array' => ['year' => 2024],
					'fast_array_sensitive' => ['password' => 'pwd'],
					'fast_boolean' => true,
					'fast_boolean_0' => false,
					'fast_float' => 3.14,
					'fast_float_sensitive' => 1.41,
					'fast_int' => 11,
					'fast_int_sensitive' => 2024,
					'fast_string' => 'f_value',
					'fast_string_sensitive' => 'fs_value',
					'key1' => 'value1',
					'key22' => '31',
					'lazy_array' => ['month' => 'October'],
					'lazy_array_sensitive' => ['password' => 'qwerty'],
					'lazy_boolean' => true,
					'lazy_boolean_0' => false,
					'lazy_float' => 3.14159,
					'lazy_float_sensitive' => 1.4142,
					'lazy_int' => 12,
					'lazy_int_sensitive' => 2048,
					'lazy_string' => 'l_value',
					'lazy_string_sensitive' => 'ls_value'
				]
			],
			[
				'user1', 'app1', 'fast_', true,
				[
					'fast_array' => ['year' => 2024],
					'fast_array_sensitive' => '***REMOVED SENSITIVE VALUE***',
					'fast_boolean' => true,
					'fast_boolean_0' => false,
					'fast_float' => 3.14,
					'fast_float_sensitive' => '***REMOVED SENSITIVE VALUE***',
					'fast_int' => 11,
					'fast_int_sensitive' => '***REMOVED SENSITIVE VALUE***',
					'fast_string' => 'f_value',
					'fast_string_sensitive' => '***REMOVED SENSITIVE VALUE***',
				]
			],
			[
				'user1', 'app1', 'fast_', false,
				[
					'fast_array' => ['year' => 2024],
					'fast_array_sensitive' => ['password' => 'pwd'],
					'fast_boolean' => true,
					'fast_boolean_0' => false,
					'fast_float' => 3.14,
					'fast_float_sensitive' => 1.41,
					'fast_int' => 11,
					'fast_int_sensitive' => 2024,
					'fast_string' => 'f_value',
					'fast_string_sensitive' => 'fs_value',
				]
			],
			[
				'user1', 'app1', 'key1', true,
				[
					'key1' => 'value1',
				]
			],
			[
				'user2', 'app1', '', false,
				[
					'1' => 'value1',
					'4' => 42,
					'5' => 17.42,
					'6' => true,
					'2' => 'value2',
					'3' => 17,
				]
			],
			[
				'user2', 'app1', '', true,
				[
					'1' => 'value1',
					'4' => '***REMOVED SENSITIVE VALUE***',
					'5' => 17.42,
					'6' => true,
					'2' => '***REMOVED SENSITIVE VALUE***',
					'3' => 17,
				]
			],
		];
	}

	/**
	 * @dataProvider providerGetValues
	 */
	public function testGetValues(
		string $userId,
		string $appId,
		string $prefix,
		bool $filtered,
		array $result,
	): void {
		$preferences = $this->generateUserPreferences();
		$this->assertJsonStringEqualsJsonString(
			json_encode($result), json_encode($preferences->getValues($userId, $appId, $prefix, $filtered))
		);
	}

	public function providerGetAllValues(): array {
		return [
			[
				'user2', false,
				[
					'app1' => [
						'1' => 'value1',
						'4' => 42,
						'5' => 17.42,
						'6' => true,
						'2' => 'value2',
						'3' => 17,
					],
					'app2' => [
						'key2' => 'value2b',
						'key3' => 'value3',
						'key4' => 'value4',
						'key8' => 12,
					],
					'app3' => [
						'key10' => false,
					],
					'only-lazy' => [
						'key1' => 'value1',
					]
				],
			],
			[
				'user2', true,
				[
					'app1' => [
						'1' => 'value1',
						'4' => '***REMOVED SENSITIVE VALUE***',
						'5' => 17.42,
						'6' => true,
						'2' => '***REMOVED SENSITIVE VALUE***',
						'3' => 17,
					],
					'app2' => [
						'key2' => 'value2b',
						'key3' => 'value3',
						'key4' => '***REMOVED SENSITIVE VALUE***',
						'key8' => 12,
					],
					'app3' => [
						'key10' => false,
					],
					'only-lazy' => [
						'key1' => 'value1',
					]
				],
			],
			[
				'user3', true,
				[
					'app2' => [
						'key2' => 'value2c',
						'key3' => 'value3',
						'key4' => '***REMOVED SENSITIVE VALUE***',
						'fast_string_sensitive' => '***REMOVED SENSITIVE VALUE***',
						'lazy_string_sensitive' => '***REMOVED SENSITIVE VALUE***',
					],
					'only-lazy' => [
						'key3' => 'value3',
					]
				],
			],
			[
				'user3', false,
				[
					'app2' => [
						'key2' => 'value2c',
						'key3' => 'value3',
						'key4' => 'value4',
						'fast_string_sensitive' => 'fs_value',
						'lazy_string_sensitive' => 'ls_value',
					],
					'only-lazy' => [
						'key3' => 'value3',
					]
				],
			],
		];
	}

	/**
	 * @dataProvider providerGetAllValues
	 */
	public function testGetAllValues(
		string $userId,
		bool $filtered,
		array $result,
	): void {
		$preferences = $this->generateUserPreferences();
		$this->assertEqualsCanonicalizing($result, $preferences->getAllValues($userId, $filtered));
	}

	public function providerSearchValuesByApps(): array {
		return [
			[
				'user1', 'key1', false, null,
				[
					'app1' => 'value1',
					'app3' => 'value123'
				]
			],
			[
				'user1', 'key1', true, null,
				[
					'only-lazy' => 'value456'
				]
			],
			[
				'user1', 'key8', false, null,
				[
					'app2' => 11,
					'app3' => 12,
				]
			],
			[
				'user1', 'key9', false, ValueType::INT,
				[
					'app2' => 0,
					'app3' => 0,
				]
			]
		];
	}

	/**
	 * @dataProvider providerSearchValuesByApps
	 */
	public function testSearchValuesByApps(
		string $userId,
		string $key,
		bool $lazy,
		?ValueType $typedAs,
		array $result,
	): void {
		$preferences = $this->generateUserPreferences();
		$this->assertEquals($result, $preferences->getValuesByApps($userId, $key, $lazy, $typedAs));
	}

	public function providerSearchValuesByUsers(): array {
		return [
			[
				'app2', 'key2', null, null,
				[
					'user1' => 'value2a',
					'user2' => 'value2b',
					'user3' => 'value2c',
					'user4' => 'value2A'
				]
			],
			[
				'app2', 'key2', null, ['user1', 'user3'],
				[
					'user1' => 'value2a',
					'user3' => 'value2c',
				]
			],
			[
				'app2', 'key2', ValueType::INT, ['user1', 'user3'],
				[
					'user1' => 0,
					'user3' => 0,
				]
			],
			[
				'app2', 'key8', ValueType::INT, null,
				[
					'user1' => 11,
					'user2' => 12,
					'user5' => 12,
				]
			],
		];
	}

	/**
	 * @dataProvider providerSearchValuesByUsers
	 */
	public function testSearchValuesByUsers(
		string $app,
		string $key,
		?ValueType $typedAs = null,
		?array $userIds = null,
		array $result,
	): void {
		$preferences = $this->generateUserPreferences();
		$this->assertEqualsCanonicalizing(
			$result, $preferences->getValuesByUsers($app, $key, $typedAs, $userIds)
		);
	}

	public function providerSearchValuesByValueString(): array {
		return [
			['app2', 'key2', 'value2a', false, ['user1']],
			['app2', 'key2', 'value2A', false, ['user4']],
			['app2', 'key2', 'value2A', true, ['user1', 'user4']]
		];
	}

	/**
	 * @dataProvider providerSearchValuesByValueString
	 */
	public function testSearchUsersByValueString(
		string $app,
		string $key,
		string|array $value,
		bool $ci,
		array $result,
	): void {
		$preferences = $this->generateUserPreferences();
		$this->assertEqualsCanonicalizing($result, iterator_to_array($preferences->searchUsersByValueString($app, $key, $value, $ci)));
	}

	public function providerSearchValuesByValueInt(): array {
		return [
			['app3', 'key8', 12, []], // sensitive value, cannot search
			['app2', 'key8', 12, ['user2', 'user5']], // sensitive value, cannot search
			['only-lazy', 'key1', 123, ['user4']],
		];
	}

	/**
	 * @dataProvider providerSearchValuesByValueInt
	 */
	public function testSearchUsersByValueInt(
		string $app,
		string $key,
		int $value,
		array $result,
	): void {
		$preferences = $this->generateUserPreferences();
		$this->assertEqualsCanonicalizing($result, iterator_to_array($preferences->searchUsersByValueInt($app, $key, $value)));
	}

	public function providerSearchValuesByValues(): array {
		return [
			['app2', 'key2', ['value2a', 'value2b'], ['user1', 'user2']],
			['app2', 'key2', ['value2a', 'value2c'], ['user1', 'user3']],
		];
	}

	/**
	 * @dataProvider providerSearchValuesByValues
	 */
	public function testSearchUsersByValues(
		string $app,
		string $key,
		array $values,
		array $result,
	): void {
		$preferences = $this->generateUserPreferences();
		$this->assertEqualsCanonicalizing($result, iterator_to_array($preferences->searchUsersByValues($app, $key, $values)));
	}

	public function providerSearchValuesByValueBool(): array {
		return [
			['app3', 'key10', true, ['user1', 'user4']],
			['app3', 'key10', false, ['user2']],
		];
	}

	/**
	 * @dataProvider providerSearchValuesByValueBool
	 */
	public function testSearchUsersByValueBool(
		string $app,
		string $key,
		bool $value,
		array $result,
	): void {
		$preferences = $this->generateUserPreferences();
		$this->assertEqualsCanonicalizing($result, iterator_to_array($preferences->searchUsersByValueBool($app, $key, $value)));
	}

	public function providerGetValueMixed(): array {
		return [
			[
				['user1'], 'user1', 'app1', 'key0', 'default_because_unknown_key', true,
				'default_because_unknown_key'
			],
			[
				null, 'user1', 'app1', 'key0', 'default_because_unknown_key', true,
				'default_because_unknown_key'
			],
			[
				['user1'], 'user1', 'app1', 'key0', 'default_because_unknown_key', false,
				'default_because_unknown_key'
			],
			[
				null, 'user1', 'app1', 'key0', 'default_because_unknown_key', false,
				'default_because_unknown_key'
			],
			[['user1'], 'user1', 'app1', 'fast_string', 'default_because_unknown_key', false, 'f_value'],
			[null, 'user1', 'app1', 'fast_string', 'default_because_unknown_key', false, 'f_value'],
			[['user1'], 'user1', 'app1', 'fast_string', 'default_because_unknown_key', true, 'f_value'],
			// because non-lazy are already loaded
			[
				null, 'user1', 'app1', 'fast_string', 'default_because_unknown_key', true,
				'default_because_unknown_key'
			],
			[
				['user1'], 'user1', 'app1', 'lazy_string', 'default_because_unknown_key', false,
				'default_because_unknown_key'
			],
			[
				null, 'user1', 'app1', 'lazy_string', 'default_because_unknown_key', false,
				'default_because_unknown_key'
			],
			[['user1'], 'user1', 'app1', 'lazy_string', 'default_because_unknown_key', true, 'l_value'],
			[null, 'user1', 'app1', 'lazy_string', 'default_because_unknown_key', true, 'l_value'],
			[
				['user1'], 'user1', 'app1', 'fast_string_sensitive', 'default_because_unknown_key', false,
				'fs_value'
			],
			[
				null, 'user1', 'app1', 'fast_string_sensitive', 'default_because_unknown_key', false,
				'fs_value'
			],
			[
				['user1'], 'user1', 'app1', 'fast_string_sensitive', 'default_because_unknown_key', true,
				'fs_value'
			],
			[
				null, 'user1', 'app1', 'fast_string_sensitive', 'default_because_unknown_key', true,
				'default_because_unknown_key'
			],
			[
				['user1'], 'user1', 'app1', 'lazy_string_sensitive', 'default_because_unknown_key', false,
				'default_because_unknown_key'
			],
			[
				null, 'user1', 'app1', 'lazy_string_sensitive', 'default_because_unknown_key', false,
				'default_because_unknown_key'
			],
			[
				['user1'], 'user1', 'app1', 'lazy_string_sensitive', 'default_because_unknown_key', true,
				'ls_value'
			],
			[null, 'user1', 'app1', 'lazy_string_sensitive', 'default_because_unknown_key', true, 'ls_value'],
		];
	}

	/**
	 * @dataProvider providerGetValueMixed
	 */
	public function testGetValueMixed(
		?array $preload,
		string $userId,
		string $app,
		string $key,
		string $default,
		bool $lazy,
		string $result,
	): void {
		$preferences = $this->generateUserPreferences($preload ?? []);
		$this->assertEquals($result, $preferences->getValueMixed($userId, $app, $key, $default, $lazy));
	}

	/**
	 * @dataProvider providerGetValueMixed
	 */
	public function testGetValueString(
		?array $preload,
		string $userId,
		string $app,
		string $key,
		string $default,
		bool $lazy,
		string $result,
	): void {
		$preferences = $this->generateUserPreferences($preload ?? []);
		$this->assertEquals($result, $preferences->getValueString($userId, $app, $key, $default, $lazy));
	}

	public function providerGetValueInt(): array {
		return [
			[['user1'], 'user1', 'app1', 'key0', 54321, true, 54321],
			[null, 'user1', 'app1', 'key0', 54321, true, 54321],
			[['user1'], 'user1', 'app1', 'key0', 54321, false, 54321],
			[null, 'user1', 'app1', 'key0', 54321, false, 54321],
			[null, 'user1', 'app1', 'key22', 54321, false, 31],
			[['user1'], 'user1', 'app1', 'fast_int', 54321, false, 11],
			[null, 'user1', 'app1', 'fast_int', 54321, false, 11],
			[['user1'], 'user1', 'app1', 'fast_int', 54321, true, 11],
			[null, 'user1', 'app1', 'fast_int', 54321, true, 54321],
			[['user1'], 'user1', 'app1', 'fast_int_sensitive', 54321, false, 2024],
			[null, 'user1', 'app1', 'fast_int_sensitive', 54321, false, 2024],
			[['user1'], 'user1', 'app1', 'fast_int_sensitive', 54321, true, 2024],
			[null, 'user1', 'app1', 'fast_int_sensitive', 54321, true, 54321],
			[['user1'], 'user1', 'app1', 'lazy_int', 54321, false, 54321],
			[null, 'user1', 'app1', 'lazy_int', 54321, false, 54321],
			[['user1'], 'user1', 'app1', 'lazy_int', 54321, true, 12],
			[null, 'user1', 'app1', 'lazy_int', 54321, true, 12],
			[['user1'], 'user1', 'app1', 'lazy_int_sensitive', 54321, false, 54321],
			[null, 'user1', 'app1', 'lazy_int_sensitive', 54321, false, 54321],
			[['user1'], 'user1', 'app1', 'lazy_int_sensitive', 54321, true, 2048],
			[null, 'user1', 'app1', 'lazy_int_sensitive', 54321, true, 2048],
		];
	}

	/**
	 * @dataProvider providerGetValueInt
	 */
	public function testGetValueInt(
		?array $preload,
		string $userId,
		string $app,
		string $key,
		int $default,
		bool $lazy,
		int $result,
	): void {
		$preferences = $this->generateUserPreferences($preload ?? []);
		$this->assertEquals($result, $preferences->getValueInt($userId, $app, $key, $default, $lazy));
	}

	public function providerGetValueFloat(): array {
		return [
			[['user1'], 'user1', 'app1', 'key0', 54.321, true, 54.321],
			[null, 'user1', 'app1', 'key0', 54.321, true, 54.321],
			[['user1'], 'user1', 'app1', 'key0', 54.321, false, 54.321],
			[null, 'user1', 'app1', 'key0', 54.321, false, 54.321],
			[['user1'], 'user1', 'app1', 'fast_float', 54.321, false, 3.14],
			[null, 'user1', 'app1', 'fast_float', 54.321, false, 3.14],
			[['user1'], 'user1', 'app1', 'fast_float', 54.321, true, 3.14],
			[null, 'user1', 'app1', 'fast_float', 54.321, true, 54.321],
			[['user1'], 'user1', 'app1', 'fast_float_sensitive', 54.321, false, 1.41],
			[null, 'user1', 'app1', 'fast_float_sensitive', 54.321, false, 1.41],
			[['user1'], 'user1', 'app1', 'fast_float_sensitive', 54.321, true, 1.41],
			[null, 'user1', 'app1', 'fast_float_sensitive', 54.321, true, 54.321],
			[['user1'], 'user1', 'app1', 'lazy_float', 54.321, false, 54.321],
			[null, 'user1', 'app1', 'lazy_float', 54.321, false, 54.321],
			[['user1'], 'user1', 'app1', 'lazy_float', 54.321, true, 3.14159],
			[null, 'user1', 'app1', 'lazy_float', 54.321, true, 3.14159],
			[['user1'], 'user1', 'app1', 'lazy_float_sensitive', 54.321, false, 54.321],
			[null, 'user1', 'app1', 'lazy_float_sensitive', 54.321, false, 54.321],
			[['user1'], 'user1', 'app1', 'lazy_float_sensitive', 54.321, true, 1.4142],
			[null, 'user1', 'app1', 'lazy_float_sensitive', 54.321, true, 1.4142],
		];
	}

	/**
	 * @dataProvider providerGetValueFloat
	 */
	public function testGetValueFloat(
		?array $preload,
		string $userId,
		string $app,
		string $key,
		float $default,
		bool $lazy,
		float $result,
	): void {
		$preferences = $this->generateUserPreferences($preload ?? []);
		$this->assertEquals($result, $preferences->getValueFloat($userId, $app, $key, $default, $lazy));
	}

	public function providerGetValueBool(): array {
		return [
			[['user1'], 'user1', 'app1', 'key0', false, true, false],
			[null, 'user1', 'app1', 'key0', false, true, false],
			[['user1'], 'user1', 'app1', 'key0', true, true, true],
			[null, 'user1', 'app1', 'key0', true, true, true],
			[['user1'], 'user1', 'app1', 'key0', false, false, false],
			[null, 'user1', 'app1', 'key0', false, false, false],
			[['user1'], 'user1', 'app1', 'key0', true, false, true],
			[null, 'user1', 'app1', 'key0', true, false, true],
			[['user1'], 'user1', 'app1', 'fast_boolean', false, false, true],
			[null, 'user1', 'app1', 'fast_boolean', false, false, true],
			[['user1'], 'user1', 'app1', 'fast_boolean_0', false, false, false],
			[null, 'user1', 'app1', 'fast_boolean_0', false, false, false],
			[['user1'], 'user1', 'app1', 'fast_boolean', true, false, true],
			[null, 'user1', 'app1', 'fast_boolean', true, false, true],
			[['user1'], 'user1', 'app1', 'fast_boolean_0', true, false, false],
			[null, 'user1', 'app1', 'fast_boolean_0', true, false, false],
			[['user1'], 'user1', 'app1', 'fast_boolean', false, true, true],
			[null, 'user1', 'app1', 'fast_boolean', false, true, false],
			[['user1'], 'user1', 'app1', 'fast_boolean_0', false, true, false],
			[null, 'user1', 'app1', 'fast_boolean_0', false, true, false],
			[['user1'], 'user1', 'app1', 'fast_boolean', true, true, true],
			[null, 'user1', 'app1', 'fast_boolean', true, true, true],
			[['user1'], 'user1', 'app1', 'fast_boolean_0', true, true, false],
			[null, 'user1', 'app1', 'fast_boolean_0', true, true, true],
			[['user1'], 'user1', 'app1', 'lazy_boolean', false, false, false],
			[null, 'user1', 'app1', 'lazy_boolean', false, false, false],
			[['user1'], 'user1', 'app1', 'lazy_boolean_0', false, false, false],
			[null, 'user1', 'app1', 'lazy_boolean_0', false, false, false],
			[['user1'], 'user1', 'app1', 'lazy_boolean', true, false, true],
			[null, 'user1', 'app1', 'lazy_boolean', true, false, true],
			[['user1'], 'user1', 'app1', 'lazy_boolean_0', true, false, true],
			[null, 'user1', 'app1', 'lazy_boolean_0', true, false, true],
			[['user1'], 'user1', 'app1', 'lazy_boolean', false, true, true],
			[null, 'user1', 'app1', 'lazy_boolean', false, true, true],
			[['user1'], 'user1', 'app1', 'lazy_boolean_0', false, true, false],
			[null, 'user1', 'app1', 'lazy_boolean_0', false, true, false],
			[['user1'], 'user1', 'app1', 'lazy_boolean', true, true, true],
			[null, 'user1', 'app1', 'lazy_boolean', true, true, true],
			[['user1'], 'user1', 'app1', 'lazy_boolean_0', true, true, false],
			[null, 'user1', 'app1', 'lazy_boolean_0', true, true, false],
		];
	}

	/**
	 * @dataProvider providerGetValueBool
	 */
	public function testGetValueBool(
		?array $preload,
		string $userId,
		string $app,
		string $key,
		bool $default,
		bool $lazy,
		bool $result,
	): void {
		$preferences = $this->generateUserPreferences($preload ?? []);
		$this->assertEquals($result, $preferences->getValueBool($userId, $app, $key, $default, $lazy));
	}

	public function providerGetValueArray(): array {
		return [
			[
				['user1'], 'user1', 'app1', 'key0', ['default_because_unknown_key'], true,
				['default_because_unknown_key']
			],
			[
				null, 'user1', 'app1', 'key0', ['default_because_unknown_key'], true,
				['default_because_unknown_key']
			],
			[
				['user1'], 'user1', 'app1', 'key0', ['default_because_unknown_key'], false,
				['default_because_unknown_key']
			],
			[
				null, 'user1', 'app1', 'key0', ['default_because_unknown_key'], false,
				['default_because_unknown_key']
			],
		];
	}

	/**
	 * @dataProvider providerGetValueArray
	 */
	public function testGetValueArray(
		?array $preload,
		string $userId,
		string $app,
		string $key,
		array $default,
		bool $lazy,
		array $result,
	): void {
		$preferences = $this->generateUserPreferences($preload ?? []);
		$this->assertEqualsCanonicalizing(
			$result, $preferences->getValueArray($userId, $app, $key, $default, $lazy)
		);
	}

	public function providerGetValueType(): array {
		return [
			[null, 'user1', 'app1', 'key1', false, ValueType::MIXED],
			[null, 'user1', 'app1', 'key1', true, null, UnknownKeyException::class],
			[null, 'user1', 'app1', 'fast_string', true, ValueType::STRING, UnknownKeyException::class],
			[['user1'], 'user1', 'app1', 'fast_string', true, ValueType::STRING],
			[null, 'user1', 'app1', 'fast_string', false, ValueType::STRING],
			[null, 'user1', 'app1', 'lazy_string', true, ValueType::STRING],
			[null, 'user1', 'app1', 'lazy_string', false, ValueType::STRING, UnknownKeyException::class],
			[
				null, 'user1', 'app1', 'fast_string_sensitive', true, ValueType::STRING,
				UnknownKeyException::class
			],
			[['user1'], 'user1', 'app1', 'fast_string_sensitive', true, ValueType::STRING],
			[null, 'user1', 'app1', 'fast_string_sensitive', false, ValueType::STRING],
			[null, 'user1', 'app1', 'lazy_string_sensitive', true, ValueType::STRING],
			[
				null, 'user1', 'app1', 'lazy_string_sensitive', false, ValueType::STRING,
				UnknownKeyException::class
			],
			[null, 'user1', 'app1', 'fast_int', true, ValueType::INT, UnknownKeyException::class],
			[['user1'], 'user1', 'app1', 'fast_int', true, ValueType::INT],
			[null, 'user1', 'app1', 'fast_int', false, ValueType::INT],
			[null, 'user1', 'app1', 'lazy_int', true, ValueType::INT],
			[null, 'user1', 'app1', 'lazy_int', false, ValueType::INT, UnknownKeyException::class],
			[null, 'user1', 'app1', 'fast_float', true, ValueType::FLOAT, UnknownKeyException::class],
			[['user1'], 'user1', 'app1', 'fast_float', true, ValueType::FLOAT],
			[null, 'user1', 'app1', 'fast_float', false, ValueType::FLOAT],
			[null, 'user1', 'app1', 'lazy_float', true, ValueType::FLOAT],
			[null, 'user1', 'app1', 'lazy_float', false, ValueType::FLOAT, UnknownKeyException::class],
			[null, 'user1', 'app1', 'fast_boolean', true, ValueType::BOOL, UnknownKeyException::class],
			[['user1'], 'user1', 'app1', 'fast_boolean', true, ValueType::BOOL],
			[null, 'user1', 'app1', 'fast_boolean', false, ValueType::BOOL],
			[null, 'user1', 'app1', 'lazy_boolean', true, ValueType::BOOL],
			[null, 'user1', 'app1', 'lazy_boolean', false, ValueType::BOOL, UnknownKeyException::class],
		];
	}

	/**
	 * @dataProvider providerGetValueType
	 */
	public function testGetValueType(
		?array $preload,
		string $userId,
		string $app,
		string $key,
		?bool $lazy,
		?ValueType $result,
		?string $exception = null,
	): void {
		$preferences = $this->generateUserPreferences($preload ?? []);
		if ($exception !== null) {
			$this->expectException($exception);
		}

		$type = $preferences->getValueType($userId, $app, $key, $lazy);
		if ($exception === null) {
			$this->assertEquals($result->value, $type->value);
		}
	}

	public function providerSetValueMixed(): array {
		return [
			[null, 'user1', 'app1', 'key1', 'value', false, false, true],
			[null, 'user1', 'app1', 'key1', '12345', true, false, true],
			[null, 'user1', 'app1', 'key1', '12345', true, true, true],
			[null, 'user1', 'app1', 'key1', 'value1', false, false, false],
			[null, 'user1', 'app1', 'key1', 'value1', true, false, true],
			[null, 'user1', 'app1', 'key1', 'value1', false, true, true],
			[
				null, 'user1', 'app1', 'fast_string', 'f_value_2', false, false, true,
				TypeConflictException::class
			],
			[
				null, 'user1', 'app1', 'fast_string', 'f_value', true, false, true,
				TypeConflictException::class
			],
			[null, 'user1', 'app1', 'fast_string', 'f_value', true, true, true, TypeConflictException::class],
			[null, 'user1', 'app1', 'fast_int', 'n_value', false, false, true, TypeConflictException::class],
			[
				null, 'user1', 'app1', 'fast_float', 'n_value', false, false, true,
				TypeConflictException::class
			],
			[
				null, 'user1', 'app1', 'lazy_string', 'l_value_2', false, false, true,
				TypeConflictException::class
			],
			[null, 'user1', 'app1', 'lazy_string', 'l_value', true, false, false],
			[null, 'user1', 'app1', 'lazy_string', 'l_value', true, true, true, TypeConflictException::class],
			[null, 'user1', 'app1', 'lazy_int', 'l_value', false, false, true, TypeConflictException::class],
			[
				null, 'user1', 'app1', 'lazy_float', 'l_value', false, false, true,
				TypeConflictException::class
			],
		];
	}

	/**
	 * @dataProvider providerSetValueMixed
	 */
	public function testSetValueMixed(
		?array $preload,
		string $userId,
		string $app,
		string $key,
		string $value,
		bool $lazy,
		bool $sensitive,
		bool $result,
		?string $exception = null,
	): void {
		$preferences = $this->generateUserPreferences($preload ?? []);
		if ($exception !== null) {
			$this->expectException($exception);
		}

		$edited = $preferences->setValueMixed($userId, $app, $key, $value, $lazy, ($sensitive) ? 1 : 0);

		if ($exception === null) {
			$this->assertEquals($result, $edited);
		}
	}


	public function providerSetValueString(): array {
		return [
			[null, 'user1', 'app1', 'key1', 'value', false, false, true],
			[null, 'user1', 'app1', 'key1', '12345', true, false, true],
			[null, 'user1', 'app1', 'key1', '12345', true, true, true],
			[null, 'user1', 'app1', 'key1', 'value1', false, false, false],
			[null, 'user1', 'app1', 'key1', 'value1', true, false, true],
			[null, 'user1', 'app1', 'key1', 'value1', false, true, true],
			[null, 'user1', 'app1', 'fast_string', 'f_value_2', false, false, true],
			[null, 'user1', 'app1', 'fast_string', 'f_value', false, false, false],
			[null, 'user1', 'app1', 'fast_string', 'f_value', true, false, true],
			[null, 'user1', 'app1', 'fast_string', 'f_value', true, true, true],
			[null, 'user1', 'app1', 'lazy_string', 'l_value_2', false, false, true],
			[null, 'user1', 'app1', 'lazy_string', 'l_value', true, false, false],
			[null, 'user1', 'app1', 'lazy_string', 'l_value', true, true, true],
			[null, 'user1', 'app1', 'fast_string_sensitive', 'fs_value', false, true, false],
			[null, 'user1', 'app1', 'fast_string_sensitive', 'fs_value', true, true, true],
			[null, 'user1', 'app1', 'fast_string_sensitive', 'fs_value', true, false, true],
			[null, 'user1', 'app1', 'lazy_string_sensitive', 'ls_value', false, true, true],
			[null, 'user1', 'app1', 'lazy_string_sensitive', 'ls_value', true, true, false],
			[null, 'user1', 'app1', 'lazy_string_sensitive', 'ls_value', true, false, false],
			[null, 'user1', 'app1', 'lazy_string_sensitive', 'ls_value_2', true, false, true],
			[null, 'user1', 'app1', 'fast_int', 'n_value', false, false, true, TypeConflictException::class],
			[
				null, 'user1', 'app1', 'fast_float', 'n_value', false, false, true,
				TypeConflictException::class
			],
			[
				null, 'user1', 'app1', 'fast_float', 'n_value', false, false, true,
				TypeConflictException::class
			],
			[null, 'user1', 'app1', 'lazy_int', 'n_value', false, false, true, TypeConflictException::class],
			[
				null, 'user1', 'app1', 'lazy_boolean', 'n_value', false, false, true,
				TypeConflictException::class
			],
			[
				null, 'user1', 'app1', 'lazy_float', 'n_value', false, false, true,
				TypeConflictException::class
			],
		];
	}

	/**
	 * @dataProvider providerSetValueString
	 */
	public function testSetValueString(
		?array $preload,
		string $userId,
		string $app,
		string $key,
		string $value,
		bool $lazy,
		bool $sensitive,
		bool $result,
		?string $exception = null,
	): void {
		$preferences = $this->generateUserPreferences($preload ?? []);
		if ($exception !== null) {
			$this->expectException($exception);
		}

		$edited = $preferences->setValueString($userId, $app, $key, $value, $lazy, ($sensitive) ? 1 : 0);
		if ($exception !== null) {
			return;
		}

		$this->assertEquals($result, $edited);
		if ($result) {
			$this->assertEquals($value, $preferences->getValueString($userId, $app, $key, $value, $lazy));
			$preferences = $this->generateUserPreferences($preload ?? []);
			$this->assertEquals($value, $preferences->getValueString($userId, $app, $key, $value, $lazy));
		}
	}

	public function providerSetValueInt(): array {
		return [
			[null, 'user1', 'app1', 'key1', 12345, false, false, true],
			[null, 'user1', 'app1', 'key1', 12345, true, false, true],
			[null, 'user1', 'app1', 'key1', 12345, true, true, true],
			[null, 'user1', 'app1', 'fast_int', 11, false, false, false],
			[null, 'user1', 'app1', 'fast_int', 111, false, false, true],
			[null, 'user1', 'app1', 'fast_int', 111, true, false, true],
			[null, 'user1', 'app1', 'fast_int', 111, false, true, true],
			[null, 'user1', 'app1', 'fast_int', 11, true, false, true],
			[null, 'user1', 'app1', 'fast_int', 11, false, true, true],
			[null, 'user1', 'app1', 'lazy_int', 12, false, false, true],
			[null, 'user1', 'app1', 'lazy_int', 121, false, false, true],
			[null, 'user1', 'app1', 'lazy_int', 121, true, false, true],
			[null, 'user1', 'app1', 'lazy_int', 121, false, true, true],
			[null, 'user1', 'app1', 'lazy_int', 12, true, false, false],
			[null, 'user1', 'app1', 'lazy_int', 12, false, true, true],
			[null, 'user1', 'app1', 'fast_string', 12345, false, false, true, TypeConflictException::class],
			[null, 'user1', 'app1', 'fast_string', 12345, false, false, false, TypeConflictException::class],
			[null, 'user1', 'app1', 'fast_string', 12345, true, false, true, TypeConflictException::class],
			[null, 'user1', 'app1', 'fast_string', 12345, true, true, true, TypeConflictException::class],
			[null, 'user1', 'app1', 'lazy_string', 12345, false, false, true, TypeConflictException::class],
			[null, 'user1', 'app1', 'lazy_string', 12345, true, false, false, TypeConflictException::class],
			[null, 'user1', 'app1', 'lazy_string', 12345, true, true, true, TypeConflictException::class],
			[null, 'user1', 'app1', 'fast_float', 12345, false, false, true, TypeConflictException::class],
			[null, 'user1', 'app1', 'fast_float', 12345, false, false, true, TypeConflictException::class],
			[null, 'user1', 'app1', 'lazy_boolean', 12345, false, false, true, TypeConflictException::class],
			[null, 'user1', 'app1', 'lazy_float', 12345, false, false, true, TypeConflictException::class],
		];
	}

	/**
	 * @dataProvider providerSetValueInt
	 */
	public function testSetValueInt(
		?array $preload,
		string $userId,
		string $app,
		string $key,
		int $value,
		bool $lazy,
		bool $sensitive,
		bool $result,
		?string $exception = null,
	): void {
		$preferences = $this->generateUserPreferences($preload ?? []);
		if ($exception !== null) {
			$this->expectException($exception);
		}

		$edited = $preferences->setValueInt($userId, $app, $key, $value, $lazy, ($sensitive) ? 1 : 0);

		if ($exception !== null) {
			return;
		}

		$this->assertEquals($result, $edited);
		if ($result) {
			$this->assertEquals($value, $preferences->getValueInt($userId, $app, $key, $value, $lazy));
			$preferences = $this->generateUserPreferences($preload ?? []);
			$this->assertEquals($value, $preferences->getValueInt($userId, $app, $key, $value, $lazy));
		}
	}

	public function providerSetValueFloat(): array {
		return [
			[null, 'user1', 'app1', 'key1', 12.345, false, false, true],
			[null, 'user1', 'app1', 'key1', 12.345, true, false, true],
			[null, 'user1', 'app1', 'key1', 12.345, true, true, true],
			[null, 'user1', 'app1', 'fast_float', 3.14, false, false, false],
			[null, 'user1', 'app1', 'fast_float', 3.15, false, false, true],
			[null, 'user1', 'app1', 'fast_float', 3.15, true, false, true],
			[null, 'user1', 'app1', 'fast_float', 3.15, false, true, true],
			[null, 'user1', 'app1', 'fast_float', 3.14, true, false, true],
			[null, 'user1', 'app1', 'fast_float', 3.14, false, true, true],
			[null, 'user1', 'app1', 'lazy_float', 3.14159, false, false, true],
			[null, 'user1', 'app1', 'lazy_float', 3.14158, false, false, true],
			[null, 'user1', 'app1', 'lazy_float', 3.14158, true, false, true],
			[null, 'user1', 'app1', 'lazy_float', 3.14158, false, true, true],
			[null, 'user1', 'app1', 'lazy_float', 3.14159, true, false, false],
			[null, 'user1', 'app1', 'lazy_float', 3.14159, false, true, true],
			[null, 'user1', 'app1', 'fast_string', 12.345, false, false, true, TypeConflictException::class],
			[null, 'user1', 'app1', 'fast_string', 12.345, false, false, false, TypeConflictException::class],
			[null, 'user1', 'app1', 'fast_string', 12.345, true, false, true, TypeConflictException::class],
			[null, 'user1', 'app1', 'fast_string', 12.345, true, true, true, TypeConflictException::class],
			[null, 'user1', 'app1', 'lazy_string', 12.345, false, false, true, TypeConflictException::class],
			[null, 'user1', 'app1', 'lazy_string', 12.345, true, false, false, TypeConflictException::class],
			[null, 'user1', 'app1', 'fast_array', 12.345, true, true, true, TypeConflictException::class],
			[null, 'user1', 'app1', 'fast_int', 12.345, false, false, true, TypeConflictException::class],
			[null, 'user1', 'app1', 'fast_int', 12.345, false, false, true, TypeConflictException::class],
			[null, 'user1', 'app1', 'lazy_boolean', 12.345, false, false, true, TypeConflictException::class],
		];
	}

	/**
	 * @dataProvider providerSetValueFloat
	 */
	public function testSetValueFloat(
		?array $preload,
		string $userId,
		string $app,
		string $key,
		float $value,
		bool $lazy,
		bool $sensitive,
		bool $result,
		?string $exception = null,
	): void {
		$preferences = $this->generateUserPreferences($preload ?? []);
		if ($exception !== null) {
			$this->expectException($exception);
		}

		$edited = $preferences->setValueFloat($userId, $app, $key, $value, $lazy, ($sensitive) ? 1 : 0);

		if ($exception !== null) {
			return;
		}

		$this->assertEquals($result, $edited);
		if ($result) {
			$this->assertEquals($value, $preferences->getValueFloat($userId, $app, $key, $value, $lazy));
			$preferences = $this->generateUserPreferences($preload ?? []);
			$this->assertEquals($value, $preferences->getValueFloat($userId, $app, $key, $value, $lazy));
		}
	}


	public function providerSetValueArray(): array {
		return [
			[null, 'user1', 'app1', 'key1', [], false, false, true],
			[null, 'user1', 'app1', 'key1', [], true, false, true],
			[null, 'user1', 'app1', 'key1', [], true, true, true],
			[null, 'user1', 'app1', 'fast_array', ['year' => 2024], false, false, false],
			[null, 'user1', 'app1', 'fast_array', [], false, false, true],
			[null, 'user1', 'app1', 'fast_array', [], true, false, true],
			[null, 'user1', 'app1', 'fast_array', [], false, true, true],
			[null, 'user1', 'app1', 'fast_array', ['year' => 2024], true, false, true],
			[null, 'user1', 'app1', 'fast_array', ['year' => 2024], false, true, true],
			[null, 'user1', 'app1', 'lazy_array', ['month' => 'October'], false, false, true],
			[null, 'user1', 'app1', 'lazy_array', ['month' => 'September'], false, false, true],
			[null, 'user1', 'app1', 'lazy_array', ['month' => 'September'], true, false, true],
			[null, 'user1', 'app1', 'lazy_array', ['month' => 'September'], false, true, true],
			[null, 'user1', 'app1', 'lazy_array', ['month' => 'October'], true, false, false],
			[null, 'user1', 'app1', 'lazy_array', ['month' => 'October'], false, true, true],
			[null, 'user1', 'app1', 'fast_string', [], false, false, true, TypeConflictException::class],
			[null, 'user1', 'app1', 'fast_string', [], false, false, false, TypeConflictException::class],
			[null, 'user1', 'app1', 'fast_string', [], true, false, true, TypeConflictException::class],
			[null, 'user1', 'app1', 'fast_string', [], true, true, true, TypeConflictException::class],
			[null, 'user1', 'app1', 'lazy_string', [], false, false, true, TypeConflictException::class],
			[null, 'user1', 'app1', 'lazy_string', [], true, false, false, TypeConflictException::class],
			[null, 'user1', 'app1', 'lazy_string', [], true, true, true, TypeConflictException::class],
			[null, 'user1', 'app1', 'fast_int', [], false, false, true, TypeConflictException::class],
			[null, 'user1', 'app1', 'fast_int', [], false, false, true, TypeConflictException::class],
			[null, 'user1', 'app1', 'lazy_boolean', [], false, false, true, TypeConflictException::class],
		];
	}

	/**
	 * @dataProvider providerSetValueArray
	 */
	public function testSetValueArray(
		?array $preload,
		string $userId,
		string $app,
		string $key,
		array $value,
		bool $lazy,
		bool $sensitive,
		bool $result,
		?string $exception = null,
	): void {
		$preferences = $this->generateUserPreferences($preload ?? []);
		if ($exception !== null) {
			$this->expectException($exception);
		}

		$edited = $preferences->setValueArray($userId, $app, $key, $value, $lazy, ($sensitive) ? 1 : 0);

		if ($exception !== null) {
			return;
		}

		$this->assertEquals($result, $edited);
		if ($result) {
			$this->assertEqualsCanonicalizing(
				$value, $preferences->getValueArray($userId, $app, $key, $value, $lazy)
			);
			$preferences = $this->generateUserPreferences($preload ?? []);
			$this->assertEqualsCanonicalizing(
				$value, $preferences->getValueArray($userId, $app, $key, $value, $lazy)
			);
		}
	}

	public function providerUpdateSensitive(): array {
		return [
			[null, 'user1', 'app1', 'key1', false, false],
			[['user1'], 'user1', 'app1', 'key1', false, false],
			[null, 'user1', 'app1', 'key1', true, true],
			[['user1'], 'user1', 'app1', 'key1', true, true],
		];
	}

	/**
	 * @dataProvider providerUpdateSensitive
	 */
	public function testUpdateSensitive(
		?array $preload,
		string $userId,
		string $app,
		string $key,
		bool $sensitive,
		bool $result,
		?string $exception = null,
	): void {
		$preferences = $this->generateUserPreferences($preload ?? []);
		if ($exception !== null) {
			$this->expectException($exception);
		}

		$edited = $preferences->updateSensitive($userId, $app, $key, $sensitive);
		if ($exception !== null) {
			return;
		}

		$this->assertEquals($result, $edited);
		if ($result) {
			$this->assertEquals($sensitive, $preferences->isSensitive($userId, $app, $key));
			$preferences = $this->generateUserPreferences($preload ?? []);
			$this->assertEquals($sensitive, $preferences->isSensitive($userId, $app, $key));
			if ($sensitive) {
				$this->assertEquals(true, str_starts_with(
					$preferences->statusCache()['fastCache'][$userId][$app][$key] ??
					$preferences->statusCache()['lazyCache'][$userId][$app][$key],
					'$UserPreferencesEncryption$')
				);
			}
		}
	}

	public function providerUpdateGlobalSensitive(): array {
		return [[true], [false]];
	}

	/**
	 * @dataProvider providerUpdateGlobalSensitive
	 */
	public function testUpdateGlobalSensitive(bool $sensitive): void {
		$preferences = $this->generateUserPreferences($preload ?? []);
		$app = 'app2';
		if ($sensitive) {
			$key = 'key2';
			$value = 'value2a';
		} else {
			$key = 'key4';
			$value = 'value4';
		}

		$this->assertEquals($value, $preferences->getValueString('user1', $app, $key));
		foreach (['user1', 'user2', 'user3', 'user4'] as $userId) {
			$preferences->getValueString($userId, $app, $key); // cache loading for userId
			$this->assertEquals(
				!$sensitive, str_starts_with(
					$preferences->statusCache()['fastCache'][$userId][$app][$key] ??
					$preferences->statusCache()['lazyCache'][$userId][$app][$key],
					'$UserPreferencesEncryption$'
				)
			);
		}

		$preferences->updateGlobalSensitive($app, $key, $sensitive);

		$this->assertEquals($value, $preferences->getValueString('user1', $app, $key));
		foreach (['user1', 'user2', 'user3', 'user4'] as $userId) {
			$this->assertEquals($sensitive, $preferences->isSensitive($userId, $app, $key));
			// should only work if updateGlobalSensitive drop cache
			$this->assertEquals($sensitive, str_starts_with(
				$preferences->statusCache()['fastCache'][$userId][$app][$key] ??
				$preferences->statusCache()['lazyCache'][$userId][$app][$key],
				'$UserPreferencesEncryption$')
			);
		}
	}

	public function providerUpdateLazy(): array {
		return [
			[null, 'user1', 'app1', 'key1', false, false],
			[['user1'], 'user1', 'app1', 'key1', false, false],
			[null, 'user1', 'app1', 'key1', true, true],
			[['user1'], 'user1', 'app1', 'key1', true, true],
		];
	}

	/**
	 * @dataProvider providerUpdateLazy
	 */
	public function testUpdateLazy(
		?array $preload,
		string $userId,
		string $app,
		string $key,
		bool $lazy,
		bool $result,
		?string $exception = null,
	): void {
		$preferences = $this->generateUserPreferences($preload ?? []);
		if ($exception !== null) {
			$this->expectException($exception);
		}

		$edited = $preferences->updateLazy($userId, $app, $key, $lazy);
		if ($exception !== null) {
			return;
		}

		$this->assertEquals($result, $edited);
		if ($result) {
			$this->assertEquals($lazy, $preferences->isLazy($userId, $app, $key));
			$preferences = $this->generateUserPreferences($preload ?? []);
			$this->assertEquals($lazy, $preferences->isLazy($userId, $app, $key));
		}
	}

	public function providerUpdateGlobalLazy(): array {
		return [[true], [false]];
	}

	/**
	 * @dataProvider providerUpdateGlobalLazy
	 */
	public function testUpdateGlobalLazy(bool $lazy): void {
		$preferences = $this->generateUserPreferences($preload ?? []);
		$app = 'app2';
		if ($lazy) {
			$key = 'key4';
			$value = 'value4';
		} else {
			$key = 'key3';
			$value = 'value3';
		}

		$this->assertEquals($value, $preferences->getValueString('user1', $app, $key, '', !$lazy));
		foreach (['user1', 'user2', 'user3', 'user4'] as $userId) {
			$this->assertEquals(!$lazy, $preferences->isLazy($userId, $app, $key));
		}

		$preferences->updateGlobalLazy($app, $key, $lazy);
		$this->assertEquals($value, $preferences->getValueString('user1', $app, $key, '', $lazy));
		foreach (['user1', 'user2', 'user3', 'user4'] as $userId) {
			$this->assertEquals($lazy, $preferences->isLazy($userId, $app, $key));
		}
	}

	public function providerGetDetails(): array {
		return [
			[
				'user3', 'app2', 'key2',
				[
					'userId' => 'user3',
					'app' => 'app2',
					'key' => 'key2',
					'value' => 'value2c',
					'type' => 0,
					'lazy' => false,
					'typeString' => 'mixed',
					'sensitive' => false
				]
			],
			[
				'user1', 'app1', 'lazy_int',
				[
					'userId' => 'user1',
					'app' => 'app1',
					'key' => 'lazy_int',
					'value' => 12,
					'type' => 2,
					'lazy' => true,
					'typeString' => 'int',
					'sensitive' => false
				]
			],
			[
				'user1', 'app1', 'fast_float_sensitive',
				[
					'userId' => 'user1',
					'app' => 'app1',
					'key' => 'fast_float_sensitive',
					'value' => 1.41,
					'type' => 3,
					'lazy' => false,
					'typeString' => 'float',
					'sensitive' => true
				]
			],
		];
	}

	/**
	 * @dataProvider providerGetDetails
	 */
	public function testGetDetails(string $userId, string $app, string $key, array $result): void {
		$preferences = $this->generateUserPreferences($preload ?? []);
		$this->assertEqualsCanonicalizing($result, $preferences->getDetails($userId, $app, $key));
	}


	public function providerDeletePreference(): array {
		return [
			[null, 'user1', 'app1', 'key22'],
			[['user1'], 'user1', 'app1', 'fast_string_sensitive'],
			[null, 'user1', 'app1', 'lazy_array_sensitive'],
			[['user2'], 'user1', 'app1', 'lazy_array_sensitive'],
			[null, 'user2', 'only-lazy', 'key1'],
			[['user2'], 'user2', 'only-lazy', 'key1'],
		];
	}

	/**
	 * @dataProvider providerDeletePreference
	 */
	public function testDeletePreference(
		?array $preload,
		string $userId,
		string $app,
		string $key,
	): void {
		$preferences = $this->generateUserPreferences($preload ?? []);
		$lazy = $preferences->isLazy($userId, $app, $key);

		$preferences = $this->generateUserPreferences($preload ?? []);
		$this->assertEquals(true, $preferences->hasKey($userId, $app, $key, $lazy));
		$preferences->deletePreference($userId, $app, $key);
		$this->assertEquals(false, $preferences->hasKey($userId, $app, $key, $lazy));
		$preferences = $this->generateUserPreferences($preload ?? []);
		$this->assertEquals(false, $preferences->hasKey($userId, $app, $key, $lazy));
	}

	public function providerDeleteKey(): array {
		return [
			[null, 'app2', 'key3'],
			[['user1'], 'app2', 'key3'],
			[null, 'only-lazy', 'key1'],
			[['user2'], 'only-lazy', 'key1'],
			[null, 'app2', 'lazy_string_sensitive'],
			[['user3', 'user1'], 'app2', 'lazy_string_sensitive'],
		];
	}

	/**
	 * @dataProvider providerDeleteKey
	 */
	public function testDeleteKey(
		?array $preload,
		string $app,
		string $key,
	): void {
		$preferences = $this->generateUserPreferences($preload ?? []);
		$preferences->deleteKey($app, $key);

		foreach (['user1', 'user2', 'user3', 'user4'] as $userId) {
			$this->assertEquals(false, $preferences->hasKey($userId, $app, $key, null));
			$preferencesTemp = $this->generateUserPreferences($preload ?? []);
			$this->assertEquals(false, $preferencesTemp->hasKey($userId, $app, $key, null));
		}
	}

	public function testDeleteApp(): void {
		$preferences = $this->generateUserPreferences();
		$preferences->deleteApp('only-lazy');

		foreach (['user1', 'user2', 'user3', 'user4'] as $userId) {
			$this->assertEquals(false, in_array('only-lazy', $preferences->getApps($userId)));
			$preferencesTemp = $this->generateUserPreferences();
			$this->assertEquals(false, in_array('only-lazy', $preferencesTemp->getApps($userId)));
		}
	}

	public function testDeleteAllPreferences(): void {
		$preferences = $this->generateUserPreferences();
		$preferences->deleteAllPreferences('user1');

		$this->assertEqualsCanonicalizing([], $preferences->getApps('user1'));
		$preferences = $this->generateUserPreferences();
		$this->assertEqualsCanonicalizing([], $preferences->getApps('user1'));
	}

	public function testClearCache(): void {
		$preferences = $this->generateUserPreferences(['user1', 'user2']);
		$preferences->clearCache('user1');

		$this->assertEquals(true, $preferences->statusCache()['fastLoaded']['user2']);
		$this->assertEquals(false, $preferences->statusCache()['fastLoaded']['user1']);
		$this->assertEquals('value2a', $preferences->getValueString('user1', 'app2', 'key2'));
		$this->assertEquals(false, $preferences->statusCache()['lazyLoaded']['user1']);
		$this->assertEquals(true, $preferences->statusCache()['fastLoaded']['user1']);
	}

	public function testClearCacheAll(): void {
		$preferences = $this->generateUserPreferences(['user1', 'user2']);
		$preferences->clearCacheAll();
		$this->assertEqualsCanonicalizing(
			[
				'fastLoaded' => [],
				'fastCache' => [],
				'lazyLoaded' => [],
				'lazyCache' => [],
				'valueTypes' => [],
			],
			$preferences->statusCache()
		);
	}
}
