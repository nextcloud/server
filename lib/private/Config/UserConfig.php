<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Config;

use Generator;
use InvalidArgumentException;
use JsonException;
use OC\AppFramework\Bootstrap\Coordinator;
use OCP\Config\Exceptions\IncorrectTypeException;
use OCP\Config\Exceptions\TypeConflictException;
use OCP\Config\Exceptions\UnknownKeyException;
use OCP\Config\IUserConfig;
use OCP\Config\Lexicon\Entry;
use OCP\Config\Lexicon\ILexicon;
use OCP\Config\Lexicon\Strictness;
use OCP\Config\ValueType;
use OCP\DB\Exception as DBException;
use OCP\DB\IResult;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\Security\ICrypto;
use Psr\Log\LoggerInterface;

/**
 * This class provides an easy way for apps to store user config in the
 * database.
 * Supports **lazy loading**
 *
 * ### What is lazy loading ?
 * In order to avoid loading useless user config into memory for each request,
 * only non-lazy values are now loaded.
 *
 * Once a value that is lazy is requested, all lazy values will be loaded.
 *
 * Similarly, some methods from this class are marked with a warning about ignoring
 * lazy loading. Use them wisely and only on parts of the code that are called
 * during specific requests or actions to avoid loading the lazy values all the time.
 *
 * @since 31.0.0
 */
class UserConfig implements IUserConfig {
	private const USER_MAX_LENGTH = 64;
	private const APP_MAX_LENGTH = 32;
	private const KEY_MAX_LENGTH = 64;
	private const INDEX_MAX_LENGTH = 64;
	private const ENCRYPTION_PREFIX = '$UserConfigEncryption$';
	private const ENCRYPTION_PREFIX_LENGTH = 22; // strlen(self::ENCRYPTION_PREFIX)

	/** @var array<string, array<string, array<string, mixed>>> [ass'user_id' => ['app_id' => ['key' => 'value']]] */
	private array $fastCache = [];   // cache for normal config keys
	/** @var array<string, array<string, array<string, mixed>>> ['user_id' => ['app_id' => ['key' => 'value']]] */
	private array $lazyCache = [];   // cache for lazy config keys
	/** @var array<string, array<string, array<string, array<string, mixed>>>> ['user_id' => ['app_id' => ['key' => ['type' => ValueType, 'flags' => bitflag]]]] */
	private array $valueDetails = [];  // type for all config values
	/** @var array<string, boolean> ['user_id' => bool] */
	private array $fastLoaded = [];
	/** @var array<string, boolean> ['user_id' => bool] */
	private array $lazyLoaded = [];
	/** @var array<string, array{entries: array<string, Entry>, aliases: array<string, string>, strictness: Strictness}> ['app_id' => ['strictness' => ConfigLexiconStrictness, 'entries' => ['config_key' => ConfigLexiconEntry[]]] */
	private array $configLexiconDetails = [];
	private bool $ignoreLexiconAliases = false;
	private array $strictnessApplied = [];

	public function __construct(
		protected IDBConnection $connection,
		protected IConfig $config,
		private readonly ConfigManager $configManager,
		private readonly PresetManager $presetManager,
		protected LoggerInterface $logger,
		protected ICrypto $crypto,
	) {
	}

	/**
	 * @inheritDoc
	 *
	 * @param string $appId optional id of app
	 *
	 * @return list<string> list of userIds
	 * @since 31.0.0
	 */
	public function getUserIds(string $appId = ''): array {
		$this->assertParams(app: $appId, allowEmptyUser: true, allowEmptyApp: true);

		$qb = $this->connection->getQueryBuilder();
		$qb->from('preferences');
		$qb->select('userid');
		$qb->groupBy('userid');
		if ($appId !== '') {
			$qb->where($qb->expr()->eq('appid', $qb->createNamedParameter($appId)));
		}

		$result = $qb->executeQuery();
		$rows = $result->fetchAll();
		$userIds = [];
		foreach ($rows as $row) {
			$userIds[] = $row['userid'];
		}

		return $userIds;
	}

	/**
	 * @inheritDoc
	 *
	 * @return list<string> list of app ids
	 * @since 31.0.0
	 */
	public function getApps(string $userId): array {
		$this->assertParams($userId, allowEmptyApp: true);
		$this->loadConfigAll($userId);
		$apps = array_merge(array_keys($this->fastCache[$userId] ?? []), array_keys($this->lazyCache[$userId] ?? []));
		sort($apps);

		return array_values(array_unique($apps));
	}

	/**
	 * @inheritDoc
	 *
	 * @param string $userId id of the user
	 * @param string $app id of the app
	 *
	 * @return list<string> list of stored config keys
	 * @since 31.0.0
	 */
	public function getKeys(string $userId, string $app): array {
		$this->assertParams($userId, $app);
		$this->loadConfigAll($userId);
		// array_merge() will remove numeric keys (here config keys), so addition arrays instead
		$keys = array_map('strval', array_keys(($this->fastCache[$userId][$app] ?? []) + ($this->lazyCache[$userId][$app] ?? [])));
		sort($keys);

		return array_values(array_unique($keys));
	}

	/**
	 * @inheritDoc
	 *
	 * @param string $userId id of the user
	 * @param string $app id of the app
	 * @param string $key config key
	 * @param bool|null $lazy TRUE to search within lazy loaded config, NULL to search within all config
	 *
	 * @return bool TRUE if key exists
	 * @since 31.0.0
	 */
	public function hasKey(string $userId, string $app, string $key, ?bool $lazy = false): bool {
		$this->assertParams($userId, $app, $key);
		$this->loadConfig($userId, $lazy);
		$this->matchAndApplyLexiconDefinition($userId, $app, $key);

		if ($lazy === null) {
			$appCache = $this->getValues($userId, $app);
			return isset($appCache[$key]);
		}

		if ($lazy) {
			return isset($this->lazyCache[$userId][$app][$key]);
		}

		return isset($this->fastCache[$userId][$app][$key]);
	}

	/**
	 * @inheritDoc
	 *
	 * @param string $userId id of the user
	 * @param string $app id of the app
	 * @param string $key config key
	 * @param bool|null $lazy TRUE to search within lazy loaded config, NULL to search within all config
	 *
	 * @return bool
	 * @throws UnknownKeyException if config key is not known
	 * @since 31.0.0
	 */
	public function isSensitive(string $userId, string $app, string $key, ?bool $lazy = false): bool {
		$this->assertParams($userId, $app, $key);
		$this->loadConfig($userId, $lazy);
		$this->matchAndApplyLexiconDefinition($userId, $app, $key);

		if (!isset($this->valueDetails[$userId][$app][$key])) {
			throw new UnknownKeyException('unknown config key');
		}

		return $this->isFlagged(self::FLAG_SENSITIVE, $this->valueDetails[$userId][$app][$key]['flags']);
	}

	/**
	 * @inheritDoc
	 *
	 * @param string $userId id of the user
	 * @param string $app id of the app
	 * @param string $key config key
	 * @param bool|null $lazy TRUE to search within lazy loaded config, NULL to search within all config
	 *
	 * @return bool
	 * @throws UnknownKeyException if config key is not known
	 * @since 31.0.0
	 */
	public function isIndexed(string $userId, string $app, string $key, ?bool $lazy = false): bool {
		$this->assertParams($userId, $app, $key);
		$this->loadConfig($userId, $lazy);
		$this->matchAndApplyLexiconDefinition($userId, $app, $key);

		if (!isset($this->valueDetails[$userId][$app][$key])) {
			throw new UnknownKeyException('unknown config key');
		}

		return $this->isFlagged(self::FLAG_INDEXED, $this->valueDetails[$userId][$app][$key]['flags']);
	}

	/**
	 * @inheritDoc
	 *
	 * @param string $userId id of the user
	 * @param string $app if of the app
	 * @param string $key config key
	 *
	 * @return bool TRUE if config is lazy loaded
	 * @throws UnknownKeyException if config key is not known
	 * @see IUserConfig for details about lazy loading
	 * @since 31.0.0
	 */
	public function isLazy(string $userId, string $app, string $key): bool {
		$this->matchAndApplyLexiconDefinition($userId, $app, $key);

		// there is a huge probability the non-lazy config are already loaded
		// meaning that we can start by only checking if a current non-lazy key exists
		if ($this->hasKey($userId, $app, $key, false)) {
			// meaning key is not lazy.
			return false;
		}

		// as key is not found as non-lazy, we load and search in the lazy config
		if ($this->hasKey($userId, $app, $key, true)) {
			return true;
		}

		throw new UnknownKeyException('unknown config key');
	}

	/**
	 * @inheritDoc
	 *
	 * @param string $userId id of the user
	 * @param string $app id of the app
	 * @param string $prefix config keys prefix to search
	 * @param bool $filtered TRUE to hide sensitive config values. Value are replaced by {@see IConfig::SENSITIVE_VALUE}
	 *
	 * @return array<string, string|int|float|bool|array> [key => value]
	 * @since 31.0.0
	 */
	public function getValues(
		string $userId,
		string $app,
		string $prefix = '',
		bool $filtered = false,
	): array {
		$this->assertParams($userId, $app, $prefix);
		// if we want to filter values, we need to get sensitivity
		$this->loadConfigAll($userId);
		// array_merge() will remove numeric keys (here config keys), so addition arrays instead
		$values = array_filter(
			$this->formatAppValues($userId, $app, ($this->fastCache[$userId][$app] ?? []) + ($this->lazyCache[$userId][$app] ?? []), $filtered),
			function (string $key) use ($prefix): bool {
				// filter values based on $prefix
				return str_starts_with($key, $prefix);
			}, ARRAY_FILTER_USE_KEY
		);

		return $values;
	}

	/**
	 * @inheritDoc
	 *
	 * @param string $userId id of the user
	 * @param bool $filtered TRUE to hide sensitive config values. Value are replaced by {@see IConfig::SENSITIVE_VALUE}
	 *
	 * @return array<string, array<string, string|int|float|bool|array>> [appId => [key => value]]
	 * @since 31.0.0
	 */
	public function getAllValues(string $userId, bool $filtered = false): array {
		$this->assertParams($userId, allowEmptyApp: true);
		$this->loadConfigAll($userId);

		$result = [];
		foreach ($this->getApps($userId) as $app) {
			// array_merge() will remove numeric keys (here config keys), so addition arrays instead
			$cached = ($this->fastCache[$userId][$app] ?? []) + ($this->lazyCache[$userId][$app] ?? []);
			$result[$app] = $this->formatAppValues($userId, $app, $cached, $filtered);
		}

		return $result;
	}

	/**
	 * @inheritDoc
	 *
	 * @param string $userId id of the user
	 * @param string $key config key
	 * @param bool $lazy search within lazy loaded config
	 * @param ValueType|null $typedAs enforce type for the returned values
	 *
	 * @return array<string, string|int|float|bool|array> [appId => value]
	 * @since 31.0.0
	 */
	public function getValuesByApps(string $userId, string $key, bool $lazy = false, ?ValueType $typedAs = null): array {
		$this->assertParams($userId, '', $key, allowEmptyApp: true);
		$this->loadConfig($userId, $lazy);

		/** @var array<array-key, array<array-key, mixed>> $cache */
		if ($lazy) {
			$cache = $this->lazyCache[$userId];
		} else {
			$cache = $this->fastCache[$userId];
		}

		$values = [];
		foreach (array_keys($cache) as $app) {
			if (isset($cache[$app][$key])) {
				$value = $cache[$app][$key];
				try {
					$this->decryptSensitiveValue($userId, $app, $key, $value);
					$value = $this->convertTypedValue($value, $typedAs ?? $this->getValueType($userId, $app, $key, $lazy));
				} catch (IncorrectTypeException|UnknownKeyException) {
				}
				$values[$app] = $value;
			}
		}

		return $values;
	}


	/**
	 * @inheritDoc
	 *
	 * @param string $app id of the app
	 * @param string $key config key
	 * @param ValueType|null $typedAs enforce type for the returned values
	 * @param array|null $userIds limit to a list of user ids
	 *
	 * @return array<string, string|int|float|bool|array> [userId => value]
	 * @since 31.0.0
	 */
	public function getValuesByUsers(
		string $app,
		string $key,
		?ValueType $typedAs = null,
		?array $userIds = null,
	): array {
		$this->assertParams('', $app, $key, allowEmptyUser: true);
		$this->matchAndApplyLexiconDefinition('', $app, $key);

		$qb = $this->connection->getQueryBuilder();
		$qb->select('userid', 'configvalue', 'type')
			->from('preferences')
			->where($qb->expr()->eq('appid', $qb->createNamedParameter($app)))
			->andWhere($qb->expr()->eq('configkey', $qb->createNamedParameter($key)));

		$values = [];
		// this nested function will execute current Query and store result within $values.
		$executeAndStoreValue = function (IQueryBuilder $qb) use (&$values, $typedAs): IResult {
			$result = $qb->executeQuery();
			while ($row = $result->fetch()) {
				$value = $row['configvalue'];
				try {
					$value = $this->convertTypedValue($value, $typedAs ?? ValueType::from((int)$row['type']));
				} catch (IncorrectTypeException) {
				}
				$values[$row['userid']] = $value;
			}
			return $result;
		};

		// if no userIds to filter, we execute query as it is and returns all values ...
		if ($userIds === null) {
			$result = $executeAndStoreValue($qb);
			$result->closeCursor();
			return $values;
		}

		// if userIds to filter, we chunk the list and execute the same query multiple times until we get all values
		$result = null;
		$qb->andWhere($qb->expr()->in('userid', $qb->createParameter('userIds')));
		foreach (array_chunk($userIds, 50, true) as $chunk) {
			$qb->setParameter('userIds', $chunk, IQueryBuilder::PARAM_STR_ARRAY);
			$result = $executeAndStoreValue($qb);
		}
		$result?->closeCursor();

		return $values;
	}

	/**
	 * @inheritDoc
	 *
	 * @param string $app id of the app
	 * @param string $key config key
	 * @param string $value config value
	 * @param bool $caseInsensitive non-case-sensitive search, only works if $value is a string
	 *
	 * @return Generator<string>
	 * @since 31.0.0
	 */
	public function searchUsersByValueString(string $app, string $key, string $value, bool $caseInsensitive = false): Generator {
		return $this->searchUsersByTypedValue($app, $key, $value, $caseInsensitive);
	}

	/**
	 * @inheritDoc
	 *
	 * @param string $app id of the app
	 * @param string $key config key
	 * @param int $value config value
	 *
	 * @return Generator<string>
	 * @since 31.0.0
	 */
	public function searchUsersByValueInt(string $app, string $key, int $value): Generator {
		return $this->searchUsersByValueString($app, $key, (string)$value);
	}

	/**
	 * @inheritDoc
	 *
	 * @param string $app id of the app
	 * @param string $key config key
	 * @param array $values list of config values
	 *
	 * @return Generator<string>
	 * @since 31.0.0
	 */
	public function searchUsersByValues(string $app, string $key, array $values): Generator {
		return $this->searchUsersByTypedValue($app, $key, $values);
	}

	/**
	 * @inheritDoc
	 *
	 * @param string $app id of the app
	 * @param string $key config key
	 * @param bool $value config value
	 *
	 * @return Generator<string>
	 * @since 31.0.0
	 */
	public function searchUsersByValueBool(string $app, string $key, bool $value): Generator {
		$values = ['0', 'off', 'false', 'no'];
		if ($value) {
			$values = ['1', 'on', 'true', 'yes'];
		}
		return $this->searchUsersByValues($app, $key, $values);
	}

	/**
	 * returns a list of users with config key set to a specific value, or within the list of
	 * possible values
	 *
	 * @param string $app
	 * @param string $key
	 * @param string|array $value
	 * @param bool $caseInsensitive
	 *
	 * @return Generator<string>
	 */
	private function searchUsersByTypedValue(string $app, string $key, string|array $value, bool $caseInsensitive = false): Generator {
		$this->assertParams('', $app, $key, allowEmptyUser: true);
		$this->matchAndApplyLexiconDefinition('', $app, $key);

		$lexiconEntry = $this->getLexiconEntry($app, $key);
		if ($lexiconEntry?->isFlagged(self::FLAG_INDEXED) === false) {
			$this->logger->notice('UserConfig+Lexicon: using searchUsersByTypedValue on config key ' . $app . '/' . $key . ' which is not set as indexed');
		}

		$qb = $this->connection->getQueryBuilder();
		$qb->from('preferences');
		$qb->select('userid');
		$qb->where($qb->expr()->eq('appid', $qb->createNamedParameter($app)));
		$qb->andWhere($qb->expr()->eq('configkey', $qb->createNamedParameter($key)));

		$configValueColumn = ($this->connection->getDatabaseProvider() === IDBConnection::PLATFORM_ORACLE) ? $qb->expr()->castColumn('configvalue', IQueryBuilder::PARAM_STR) : 'configvalue';
		if (is_array($value)) {
			$where = $qb->expr()->in('indexed', $qb->createNamedParameter($value, IQueryBuilder::PARAM_STR_ARRAY));
			// in case lexicon does not exist for this key - or is not set as indexed - we keep searching for non-index entries if 'flags' is set as not indexed
			if ($lexiconEntry?->isFlagged(self::FLAG_INDEXED) !== true) {
				$where = $qb->expr()->orX(
					$where,
					$qb->expr()->andX(
						$qb->expr()->neq($qb->expr()->bitwiseAnd('flags', self::FLAG_INDEXED), $qb->createNamedParameter(self::FLAG_INDEXED, IQueryBuilder::PARAM_INT)),
						$qb->expr()->in($configValueColumn, $qb->createNamedParameter($value, IQueryBuilder::PARAM_STR_ARRAY))
					)
				);
			}
		} else {
			if ($caseInsensitive) {
				$where = $qb->expr()->eq($qb->func()->lower('indexed'), $qb->createNamedParameter(strtolower($value)));
				// in case lexicon does not exist for this key - or is not set as indexed - we keep searching for non-index entries if 'flags' is set as not indexed
				if ($lexiconEntry?->isFlagged(self::FLAG_INDEXED) !== true) {
					$where = $qb->expr()->orX(
						$where,
						$qb->expr()->andX(
							$qb->expr()->neq($qb->expr()->bitwiseAnd('flags', self::FLAG_INDEXED), $qb->createNamedParameter(self::FLAG_INDEXED, IQueryBuilder::PARAM_INT)),
							$qb->expr()->eq($qb->func()->lower($configValueColumn), $qb->createNamedParameter(strtolower($value)))
						)
					);
				}
			} else {
				$where = $qb->expr()->eq('indexed', $qb->createNamedParameter($value));
				// in case lexicon does not exist for this key - or is not set as indexed - we keep searching for non-index entries if 'flags' is set as not indexed
				if ($lexiconEntry?->isFlagged(self::FLAG_INDEXED) !== true) {
					$where = $qb->expr()->orX(
						$where,
						$qb->expr()->andX(
							$qb->expr()->neq($qb->expr()->bitwiseAnd('flags', self::FLAG_INDEXED), $qb->createNamedParameter(self::FLAG_INDEXED, IQueryBuilder::PARAM_INT)),
							$qb->expr()->eq($configValueColumn, $qb->createNamedParameter($value))
						)
					);
				}
			}
		}

		$qb->andWhere($where);
		$result = $qb->executeQuery();
		while ($row = $result->fetch()) {
			yield $row['userid'];
		}
	}

	/**
	 * Get the config value as string.
	 * If the value does not exist the given default will be returned.
	 *
	 * Set lazy to `null` to ignore it and get the value from either source.
	 *
	 * **WARNING:** Method is internal and **SHOULD** not be used, as it is better to get the value with a type.
	 *
	 * @param string $userId id of the user
	 * @param string $app id of the app
	 * @param string $key config key
	 * @param string $default config value
	 * @param null|bool $lazy get config as lazy loaded or not. can be NULL
	 *
	 * @return string the value or $default
	 * @throws TypeConflictException
	 * @internal
	 * @since 31.0.0
	 * @see IUserConfig for explanation about lazy loading
	 * @see getValueString()
	 * @see getValueInt()
	 * @see getValueFloat()
	 * @see getValueBool()
	 * @see getValueArray()
	 */
	public function getValueMixed(
		string $userId,
		string $app,
		string $key,
		string $default = '',
		?bool $lazy = false,
	): string {
		$this->matchAndApplyLexiconDefinition($userId, $app, $key);
		try {
			$lazy ??= $this->isLazy($userId, $app, $key);
		} catch (UnknownKeyException) {
			return $default;
		}

		return $this->getTypedValue(
			$userId,
			$app,
			$key,
			$default,
			$lazy,
			ValueType::MIXED
		);
	}

	/**
	 * @inheritDoc
	 *
	 * @param string $userId id of the user
	 * @param string $app id of the app
	 * @param string $key config key
	 * @param string $default default value
	 * @param bool $lazy search within lazy loaded config
	 *
	 * @return string stored config value or $default if not set in database
	 * @throws InvalidArgumentException if one of the argument format is invalid
	 * @throws TypeConflictException in case of conflict with the value type set in database
	 * @since 31.0.0
	 * @see IUserConfig for explanation about lazy loading
	 */
	public function getValueString(
		string $userId,
		string $app,
		string $key,
		string $default = '',
		bool $lazy = false,
	): string {
		return $this->getTypedValue($userId, $app, $key, $default, $lazy, ValueType::STRING);
	}

	/**
	 * @inheritDoc
	 *
	 * @param string $userId id of the user
	 * @param string $app id of the app
	 * @param string $key config key
	 * @param int $default default value
	 * @param bool $lazy search within lazy loaded config
	 *
	 * @return int stored config value or $default if not set in database
	 * @throws InvalidArgumentException if one of the argument format is invalid
	 * @throws TypeConflictException in case of conflict with the value type set in database
	 * @since 31.0.0
	 * @see IUserConfig for explanation about lazy loading
	 */
	public function getValueInt(
		string $userId,
		string $app,
		string $key,
		int $default = 0,
		bool $lazy = false,
	): int {
		return (int)$this->getTypedValue($userId, $app, $key, (string)$default, $lazy, ValueType::INT);
	}

	/**
	 * @inheritDoc
	 *
	 * @param string $userId id of the user
	 * @param string $app id of the app
	 * @param string $key config key
	 * @param float $default default value
	 * @param bool $lazy search within lazy loaded config
	 *
	 * @return float stored config value or $default if not set in database
	 * @throws InvalidArgumentException if one of the argument format is invalid
	 * @throws TypeConflictException in case of conflict with the value type set in database
	 * @since 31.0.0
	 * @see IUserConfig for explanation about lazy loading
	 */
	public function getValueFloat(
		string $userId,
		string $app,
		string $key,
		float $default = 0,
		bool $lazy = false,
	): float {
		return (float)$this->getTypedValue($userId, $app, $key, (string)$default, $lazy, ValueType::FLOAT);
	}

	/**
	 * @inheritDoc
	 *
	 * @param string $userId id of the user
	 * @param string $app id of the app
	 * @param string $key config key
	 * @param bool $default default value
	 * @param bool $lazy search within lazy loaded config
	 *
	 * @return bool stored config value or $default if not set in database
	 * @throws InvalidArgumentException if one of the argument format is invalid
	 * @throws TypeConflictException in case of conflict with the value type set in database
	 * @since 31.0.0
	 * @see IUserConfig for explanation about lazy loading
	 */
	public function getValueBool(
		string $userId,
		string $app,
		string $key,
		bool $default = false,
		bool $lazy = false,
	): bool {
		$b = strtolower($this->getTypedValue($userId, $app, $key, $default ? 'true' : 'false', $lazy, ValueType::BOOL));
		return in_array($b, ['1', 'true', 'yes', 'on']);
	}

	/**
	 * @inheritDoc
	 *
	 * @param string $userId id of the user
	 * @param string $app id of the app
	 * @param string $key config key
	 * @param array $default default value
	 * @param bool $lazy search within lazy loaded config
	 *
	 * @return array stored config value or $default if not set in database
	 * @throws InvalidArgumentException if one of the argument format is invalid
	 * @throws TypeConflictException in case of conflict with the value type set in database
	 * @since 31.0.0
	 * @see IUserConfig for explanation about lazy loading
	 */
	public function getValueArray(
		string $userId,
		string $app,
		string $key,
		array $default = [],
		bool $lazy = false,
	): array {
		try {
			$defaultJson = json_encode($default, JSON_THROW_ON_ERROR);
			$value = json_decode($this->getTypedValue($userId, $app, $key, $defaultJson, $lazy, ValueType::ARRAY), true, flags: JSON_THROW_ON_ERROR);

			return is_array($value) ? $value : [];
		} catch (JsonException) {
			return [];
		}
	}

	/**
	 * @param string $userId
	 * @param string $app id of the app
	 * @param string $key config key
	 * @param string $default default value
	 * @param bool $lazy search within lazy loaded config
	 * @param ValueType $type value type
	 *
	 * @return string
	 * @throws TypeConflictException if type from database is not VALUE_MIXED and different from the requested one
	 */
	private function getTypedValue(
		string $userId,
		string $app,
		string $key,
		string $default,
		bool $lazy,
		ValueType $type,
	): string {
		$this->assertParams($userId, $app, $key);
		$origKey = $key;
		$matched = $this->matchAndApplyLexiconDefinition($userId, $app, $key, $lazy, $type, default: $default);
		if ($default === null) {
			// there is no logical reason for it to be null
			throw new \Exception('default cannot be null');
		}

		// returns default if strictness of lexicon is set to WARNING (block and report)
		if (!$matched) {
			return $default;
		}

		$this->loadConfig($userId, $lazy);

		/**
		 * We ignore check if mixed type is requested.
		 * If type of stored value is set as mixed, we don't filter.
		 * If type of stored value is defined, we compare with the one requested.
		 */
		$knownType = $this->valueDetails[$userId][$app][$key]['type'] ?? null;
		if ($type !== ValueType::MIXED
			&& $knownType !== null
			&& $knownType !== ValueType::MIXED
			&& $type !== $knownType) {
			$this->logger->warning('conflict with value type from database', ['app' => $app, 'key' => $key, 'type' => $type, 'knownType' => $knownType]);
			throw new TypeConflictException('conflict with value type from database');
		}

		/**
		 * - the pair $app/$key cannot exist in both array,
		 * - we should still return an existing non-lazy value even if current method
		 *   is called with $lazy is true
		 *
		 * This way, lazyCache will be empty until the load for lazy config value is requested.
		 */
		if (isset($this->lazyCache[$userId][$app][$key])) {
			$value = $this->lazyCache[$userId][$app][$key];
		} elseif (isset($this->fastCache[$userId][$app][$key])) {
			$value = $this->fastCache[$userId][$app][$key];
		} else {
			return $default;
		}

		$this->decryptSensitiveValue($userId, $app, $key, $value);

		// in case the key was modified while running matchAndApplyLexiconDefinition() we are
		// interested to check options in case a modification of the value is needed
		// ie inverting value from previous key when using lexicon option RENAME_INVERT_BOOLEAN
		if ($origKey !== $key && $type === ValueType::BOOL) {
			$value = ($this->configManager->convertToBool($value, $this->getLexiconEntry($app, $key))) ? '1' : '0';
		}

		return $value;
	}

	/**
	 * @inheritDoc
	 *
	 * @param string $userId id of the user
	 * @param string $app id of the app
	 * @param string $key config key
	 *
	 * @return ValueType type of the value
	 * @throws UnknownKeyException if config key is not known
	 * @throws IncorrectTypeException if config value type is not known
	 * @since 31.0.0
	 */
	public function getValueType(string $userId, string $app, string $key, ?bool $lazy = null): ValueType {
		$this->assertParams($userId, $app, $key);
		$this->loadConfig($userId, $lazy);
		$this->matchAndApplyLexiconDefinition($userId, $app, $key);

		if (!isset($this->valueDetails[$userId][$app][$key]['type'])) {
			throw new UnknownKeyException('unknown config key');
		}

		return $this->valueDetails[$userId][$app][$key]['type'];
	}

	/**
	 * @inheritDoc
	 *
	 * @param string $userId id of the user
	 * @param string $app id of the app
	 * @param string $key config key
	 * @param bool $lazy lazy loading
	 *
	 * @return int flags applied to value
	 * @throws UnknownKeyException if config key is not known
	 * @throws IncorrectTypeException if config value type is not known
	 * @since 31.0.0
	 */
	public function getValueFlags(string $userId, string $app, string $key, bool $lazy = false): int {
		$this->assertParams($userId, $app, $key);
		$this->loadConfig($userId, $lazy);
		$this->matchAndApplyLexiconDefinition($userId, $app, $key);

		if (!isset($this->valueDetails[$userId][$app][$key])) {
			throw new UnknownKeyException('unknown config key');
		}

		return $this->valueDetails[$userId][$app][$key]['flags'];
	}

	/**
	 * Store a config key and its value in database as VALUE_MIXED
	 *
	 * **WARNING:** Method is internal and **MUST** not be used as it is best to set a real value type
	 *
	 * @param string $userId id of the user
	 * @param string $app id of the app
	 * @param string $key config key
	 * @param string $value config value
	 * @param bool $lazy set config as lazy loaded
	 * @param bool $sensitive if TRUE value will be hidden when listing config values.
	 *
	 * @return bool TRUE if value was different, therefor updated in database
	 * @throws TypeConflictException if type from database is not VALUE_MIXED
	 * @internal
	 * @since 31.0.0
	 * @see IUserConfig for explanation about lazy loading
	 * @see setValueString()
	 * @see setValueInt()
	 * @see setValueFloat()
	 * @see setValueBool()
	 * @see setValueArray()
	 */
	public function setValueMixed(
		string $userId,
		string $app,
		string $key,
		string $value,
		bool $lazy = false,
		int $flags = 0,
	): bool {
		return $this->setTypedValue(
			$userId,
			$app,
			$key,
			$value,
			$lazy,
			$flags,
			ValueType::MIXED
		);
	}


	/**
	 * @inheritDoc
	 *
	 * @param string $userId id of the user
	 * @param string $app id of the app
	 * @param string $key config key
	 * @param string $value config value
	 * @param bool $lazy set config as lazy loaded
	 * @param bool $sensitive if TRUE value will be hidden when listing config values.
	 *
	 * @return bool TRUE if value was different, therefor updated in database
	 * @throws TypeConflictException if type from database is not VALUE_MIXED and different from the requested one
	 * @since 31.0.0
	 * @see IUserConfig for explanation about lazy loading
	 */
	public function setValueString(
		string $userId,
		string $app,
		string $key,
		string $value,
		bool $lazy = false,
		int $flags = 0,
	): bool {
		return $this->setTypedValue(
			$userId,
			$app,
			$key,
			$value,
			$lazy,
			$flags,
			ValueType::STRING
		);
	}

	/**
	 * @inheritDoc
	 *
	 * @param string $userId id of the user
	 * @param string $app id of the app
	 * @param string $key config key
	 * @param int $value config value
	 * @param bool $lazy set config as lazy loaded
	 * @param bool $sensitive if TRUE value will be hidden when listing config values.
	 *
	 * @return bool TRUE if value was different, therefor updated in database
	 * @throws TypeConflictException if type from database is not VALUE_MIXED and different from the requested one
	 * @since 31.0.0
	 * @see IUserConfig for explanation about lazy loading
	 */
	public function setValueInt(
		string $userId,
		string $app,
		string $key,
		int $value,
		bool $lazy = false,
		int $flags = 0,
	): bool {
		if ($value > 2000000000) {
			$this->logger->debug('You are trying to store an integer value around/above 2,147,483,647. This is a reminder that reaching this theoretical limit on 32 bits system will throw an exception.');
		}

		return $this->setTypedValue(
			$userId,
			$app,
			$key,
			(string)$value,
			$lazy,
			$flags,
			ValueType::INT
		);
	}

	/**
	 * @inheritDoc
	 *
	 * @param string $userId id of the user
	 * @param string $app id of the app
	 * @param string $key config key
	 * @param float $value config value
	 * @param bool $lazy set config as lazy loaded
	 * @param bool $sensitive if TRUE value will be hidden when listing config values.
	 *
	 * @return bool TRUE if value was different, therefor updated in database
	 * @throws TypeConflictException if type from database is not VALUE_MIXED and different from the requested one
	 * @since 31.0.0
	 * @see IUserConfig for explanation about lazy loading
	 */
	public function setValueFloat(
		string $userId,
		string $app,
		string $key,
		float $value,
		bool $lazy = false,
		int $flags = 0,
	): bool {
		return $this->setTypedValue(
			$userId,
			$app,
			$key,
			(string)$value,
			$lazy,
			$flags,
			ValueType::FLOAT
		);
	}

	/**
	 * @inheritDoc
	 *
	 * @param string $userId id of the user
	 * @param string $app id of the app
	 * @param string $key config key
	 * @param bool $value config value
	 * @param bool $lazy set config as lazy loaded
	 *
	 * @return bool TRUE if value was different, therefor updated in database
	 * @throws TypeConflictException if type from database is not VALUE_MIXED and different from the requested one
	 * @since 31.0.0
	 * @see IUserConfig for explanation about lazy loading
	 */
	public function setValueBool(
		string $userId,
		string $app,
		string $key,
		bool $value,
		bool $lazy = false,
		int $flags = 0,
	): bool {
		return $this->setTypedValue(
			$userId,
			$app,
			$key,
			($value) ? '1' : '0',
			$lazy,
			$flags,
			ValueType::BOOL
		);
	}

	/**
	 * @inheritDoc
	 *
	 * @param string $userId id of the user
	 * @param string $app id of the app
	 * @param string $key config key
	 * @param array $value config value
	 * @param bool $lazy set config as lazy loaded
	 * @param bool $sensitive if TRUE value will be hidden when listing config values.
	 *
	 * @return bool TRUE if value was different, therefor updated in database
	 * @throws TypeConflictException if type from database is not VALUE_MIXED and different from the requested one
	 * @throws JsonException
	 * @since 31.0.0
	 * @see IUserConfig for explanation about lazy loading
	 */
	public function setValueArray(
		string $userId,
		string $app,
		string $key,
		array $value,
		bool $lazy = false,
		int $flags = 0,
	): bool {
		try {
			return $this->setTypedValue(
				$userId,
				$app,
				$key,
				json_encode($value, JSON_THROW_ON_ERROR),
				$lazy,
				$flags,
				ValueType::ARRAY
			);
		} catch (JsonException $e) {
			$this->logger->warning('could not setValueArray', ['app' => $app, 'key' => $key, 'exception' => $e]);
			throw $e;
		}
	}

	/**
	 * Store a config key and its value in database
	 *
	 * If config key is already known with the exact same config value and same sensitive/lazy status, the
	 * database is not updated. If config value was previously stored as sensitive, status will not be
	 * altered.
	 *
	 * @param string $userId id of the user
	 * @param string $app id of the app
	 * @param string $key config key
	 * @param string $value config value
	 * @param bool $lazy config set as lazy loaded
	 * @param ValueType $type value type
	 *
	 * @return bool TRUE if value was updated in database
	 * @throws TypeConflictException if type from database is not VALUE_MIXED and different from the requested one
	 * @see IUserConfig for explanation about lazy loading
	 */
	private function setTypedValue(
		string $userId,
		string $app,
		string $key,
		string $value,
		bool $lazy,
		int $flags,
		ValueType $type,
	): bool {
		// Primary email addresses are always(!) expected to be lowercase
		if ($app === 'settings' && $key === 'email') {
			$value = strtolower($value);
		}

		$this->assertParams($userId, $app, $key);
		if (!$this->matchAndApplyLexiconDefinition($userId, $app, $key, $lazy, $type, $flags)) {
			// returns false as database is not updated
			return false;
		}
		$this->loadConfig($userId, $lazy);

		$inserted = $refreshCache = false;
		$origValue = $value;
		$sensitive = $this->isFlagged(self::FLAG_SENSITIVE, $flags);
		if ($sensitive || ($this->hasKey($userId, $app, $key, $lazy) && $this->isSensitive($userId, $app, $key, $lazy))) {
			$value = self::ENCRYPTION_PREFIX . $this->crypto->encrypt($value);
			$flags |= self::FLAG_SENSITIVE;
		}

		// if requested, we fill the 'indexed' field with current value
		$indexed = '';
		if ($type !== ValueType::ARRAY && $this->isFlagged(self::FLAG_INDEXED, $flags)) {
			if ($this->isFlagged(self::FLAG_SENSITIVE, $flags)) {
				$this->logger->warning('sensitive value are not to be indexed');
			} elseif (strlen($value) > self::USER_MAX_LENGTH) {
				$this->logger->warning('value is too lengthy to be indexed');
			} else {
				$indexed = $value;
			}
		}

		if ($this->hasKey($userId, $app, $key, $lazy)) {
			/**
			 * no update if key is already known with set lazy status and value is
			 * not different, unless sensitivity is switched from false to true.
			 */
			if ($origValue === $this->getTypedValue($userId, $app, $key, $value, $lazy, $type)
				&& (!$sensitive || $this->isSensitive($userId, $app, $key, $lazy))) {
				return false;
			}
		} else {
			/**
			 * if key is not known yet, we try to insert.
			 * It might fail if the key exists with a different lazy flag.
			 */
			try {
				$insert = $this->connection->getQueryBuilder();
				$insert->insert('preferences')
					->setValue('userid', $insert->createNamedParameter($userId))
					->setValue('appid', $insert->createNamedParameter($app))
					->setValue('lazy', $insert->createNamedParameter(($lazy) ? 1 : 0, IQueryBuilder::PARAM_INT))
					->setValue('type', $insert->createNamedParameter($type->value, IQueryBuilder::PARAM_INT))
					->setValue('flags', $insert->createNamedParameter($flags, IQueryBuilder::PARAM_INT))
					->setValue('indexed', $insert->createNamedParameter($indexed))
					->setValue('configkey', $insert->createNamedParameter($key))
					->setValue('configvalue', $insert->createNamedParameter($value));
				$insert->executeStatement();
				$inserted = true;
			} catch (DBException $e) {
				if ($e->getReason() !== DBException::REASON_UNIQUE_CONSTRAINT_VIOLATION) {
					// TODO: throw exception or just log and returns false !?
					throw $e;
				}
			}
		}

		/**
		 * We cannot insert a new row, meaning we need to update an already existing one
		 */
		if (!$inserted) {
			$currType = $this->valueDetails[$userId][$app][$key]['type'] ?? null;
			if ($currType === null) { // this might happen when switching lazy loading status
				$this->loadConfigAll($userId);
				$currType = $this->valueDetails[$userId][$app][$key]['type'];
			}

			/**
			 * We only log a warning and set it to VALUE_MIXED.
			 */
			if ($currType === null) {
				$this->logger->warning('Value type is set to zero (0) in database. This is not supposed to happens', ['app' => $app, 'key' => $key]);
				$currType = ValueType::MIXED;
			}

			/**
			 * we only accept a different type from the one stored in database
			 * if the one stored in database is not-defined (VALUE_MIXED)
			 */
			if ($currType !== ValueType::MIXED
				&& $currType !== $type) {
				try {
					$currTypeDef = $currType->getDefinition();
					$typeDef = $type->getDefinition();
				} catch (IncorrectTypeException) {
					$currTypeDef = $currType->value;
					$typeDef = $type->value;
				}
				throw new TypeConflictException('conflict between new type (' . $typeDef . ') and old type (' . $currTypeDef . ')');
			}

			if ($lazy !== $this->isLazy($userId, $app, $key)) {
				$refreshCache = true;
			}

			$update = $this->connection->getQueryBuilder();
			$update->update('preferences')
				->set('configvalue', $update->createNamedParameter($value))
				->set('lazy', $update->createNamedParameter(($lazy) ? 1 : 0, IQueryBuilder::PARAM_INT))
				->set('type', $update->createNamedParameter($type->value, IQueryBuilder::PARAM_INT))
				->set('flags', $update->createNamedParameter($flags, IQueryBuilder::PARAM_INT))
				->set('indexed', $update->createNamedParameter($indexed))
				->where($update->expr()->eq('userid', $update->createNamedParameter($userId)))
				->andWhere($update->expr()->eq('appid', $update->createNamedParameter($app)))
				->andWhere($update->expr()->eq('configkey', $update->createNamedParameter($key)));

			$update->executeStatement();
		}

		if ($refreshCache) {
			$this->clearCache($userId);
			return true;
		}

		// update local cache
		if ($lazy) {
			$this->lazyCache[$userId][$app][$key] = $value;
		} else {
			$this->fastCache[$userId][$app][$key] = $value;
		}
		$this->valueDetails[$userId][$app][$key] = [
			'type' => $type,
			'flags' => $flags
		];

		return true;
	}

	/**
	 * Change the type of config value.
	 *
	 * **WARNING:** Method is internal and **MUST** not be used as it may break things.
	 *
	 * @param string $userId id of the user
	 * @param string $app id of the app
	 * @param string $key config key
	 * @param ValueType $type value type
	 *
	 * @return bool TRUE if database update were necessary
	 * @throws UnknownKeyException if $key is now known in database
	 * @throws IncorrectTypeException if $type is not valid
	 * @internal
	 * @since 31.0.0
	 */
	public function updateType(string $userId, string $app, string $key, ValueType $type = ValueType::MIXED): bool {
		$this->assertParams($userId, $app, $key);
		$this->loadConfigAll($userId);
		$this->matchAndApplyLexiconDefinition($userId, $app, $key);
		$this->isLazy($userId, $app, $key); // confirm key exists

		$update = $this->connection->getQueryBuilder();
		$update->update('preferences')
			->set('type', $update->createNamedParameter($type->value, IQueryBuilder::PARAM_INT))
			->where($update->expr()->eq('userid', $update->createNamedParameter($userId)))
			->andWhere($update->expr()->eq('appid', $update->createNamedParameter($app)))
			->andWhere($update->expr()->eq('configkey', $update->createNamedParameter($key)));
		$update->executeStatement();

		$this->valueDetails[$userId][$app][$key]['type'] = $type;

		return true;
	}

	/**
	 * @inheritDoc
	 *
	 * @param string $userId id of the user
	 * @param string $app id of the app
	 * @param string $key config key
	 * @param bool $sensitive TRUE to set as sensitive, FALSE to unset
	 *
	 * @return bool TRUE if entry was found in database and an update was necessary
	 * @since 31.0.0
	 */
	public function updateSensitive(string $userId, string $app, string $key, bool $sensitive): bool {
		$this->assertParams($userId, $app, $key);
		$this->loadConfigAll($userId);
		$this->matchAndApplyLexiconDefinition($userId, $app, $key);

		try {
			if ($sensitive === $this->isSensitive($userId, $app, $key, null)) {
				return false;
			}
		} catch (UnknownKeyException) {
			return false;
		}

		$lazy = $this->isLazy($userId, $app, $key);
		if ($lazy) {
			$cache = $this->lazyCache;
		} else {
			$cache = $this->fastCache;
		}

		if (!isset($cache[$userId][$app][$key])) {
			throw new UnknownKeyException('unknown config key');
		}

		$value = $cache[$userId][$app][$key];
		$flags = $this->getValueFlags($userId, $app, $key);
		if ($sensitive) {
			$flags |= self::FLAG_SENSITIVE;
			$value = self::ENCRYPTION_PREFIX . $this->crypto->encrypt($value);
		} else {
			$flags &= ~self::FLAG_SENSITIVE;
			$this->decryptSensitiveValue($userId, $app, $key, $value);
		}

		$update = $this->connection->getQueryBuilder();
		$update->update('preferences')
			->set('flags', $update->createNamedParameter($flags, IQueryBuilder::PARAM_INT))
			->set('configvalue', $update->createNamedParameter($value))
			->where($update->expr()->eq('userid', $update->createNamedParameter($userId)))
			->andWhere($update->expr()->eq('appid', $update->createNamedParameter($app)))
			->andWhere($update->expr()->eq('configkey', $update->createNamedParameter($key)));
		$update->executeStatement();

		$this->valueDetails[$userId][$app][$key]['flags'] = $flags;

		return true;
	}

	/**
	 * @inheritDoc
	 *
	 * @param string $app
	 * @param string $key
	 * @param bool $sensitive
	 *
	 * @since 31.0.0
	 */
	public function updateGlobalSensitive(string $app, string $key, bool $sensitive): void {
		$this->assertParams('', $app, $key, allowEmptyUser: true);
		$this->matchAndApplyLexiconDefinition('', $app, $key);

		foreach (array_keys($this->getValuesByUsers($app, $key)) as $userId) {
			try {
				$this->updateSensitive($userId, $app, $key, $sensitive);
			} catch (UnknownKeyException) {
				// should not happen and can be ignored
			}
		}

		// we clear all cache
		$this->clearCacheAll();
	}

	/**
	 * @inheritDoc
	 *
	 * @param string $userId
	 * @param string $app
	 * @param string $key
	 * @param bool $indexed
	 *
	 * @return bool
	 * @throws DBException
	 * @throws IncorrectTypeException
	 * @throws UnknownKeyException
	 * @since 31.0.0
	 */
	public function updateIndexed(string $userId, string $app, string $key, bool $indexed): bool {
		$this->assertParams($userId, $app, $key);
		$this->loadConfigAll($userId);
		$this->matchAndApplyLexiconDefinition($userId, $app, $key);

		try {
			if ($indexed === $this->isIndexed($userId, $app, $key, null)) {
				return false;
			}
		} catch (UnknownKeyException) {
			return false;
		}

		$lazy = $this->isLazy($userId, $app, $key);
		if ($lazy) {
			$cache = $this->lazyCache;
		} else {
			$cache = $this->fastCache;
		}

		if (!isset($cache[$userId][$app][$key])) {
			throw new UnknownKeyException('unknown config key');
		}

		$value = $cache[$userId][$app][$key];
		$flags = $this->getValueFlags($userId, $app, $key);
		if ($indexed) {
			$indexed = $value;
		} else {
			$flags &= ~self::FLAG_INDEXED;
			$indexed = '';
		}

		$update = $this->connection->getQueryBuilder();
		$update->update('preferences')
			->set('flags', $update->createNamedParameter($flags, IQueryBuilder::PARAM_INT))
			->set('indexed', $update->createNamedParameter($indexed))
			->where($update->expr()->eq('userid', $update->createNamedParameter($userId)))
			->andWhere($update->expr()->eq('appid', $update->createNamedParameter($app)))
			->andWhere($update->expr()->eq('configkey', $update->createNamedParameter($key)));
		$update->executeStatement();

		$this->valueDetails[$userId][$app][$key]['flags'] = $flags;

		return true;
	}


	/**
	 * @inheritDoc
	 *
	 * @param string $app
	 * @param string $key
	 * @param bool $indexed
	 *
	 * @since 31.0.0
	 */
	public function updateGlobalIndexed(string $app, string $key, bool $indexed): void {
		$this->assertParams('', $app, $key, allowEmptyUser: true);
		$this->matchAndApplyLexiconDefinition('', $app, $key);

		$update = $this->connection->getQueryBuilder();
		$update->update('preferences')
			->where(
				$update->expr()->eq('appid', $update->createNamedParameter($app)),
				$update->expr()->eq('configkey', $update->createNamedParameter($key))
			);

		// switching flags 'indexed' on and off is about adding/removing the bit value on the correct entries
		if ($indexed) {
			$update->set('indexed', $update->func()->substring('configvalue', $update->createNamedParameter(1, IQueryBuilder::PARAM_INT), $update->createNamedParameter(64, IQueryBuilder::PARAM_INT)));
			$update->set('flags', $update->func()->add('flags', $update->createNamedParameter(self::FLAG_INDEXED, IQueryBuilder::PARAM_INT)));
			$update->andWhere(
				$update->expr()->neq($update->expr()->castColumn(
					$update->expr()->bitwiseAnd('flags', self::FLAG_INDEXED), IQueryBuilder::PARAM_INT), $update->createNamedParameter(self::FLAG_INDEXED, IQueryBuilder::PARAM_INT)
				));
		} else {
			// emptying field 'indexed' if key is not set as indexed anymore
			$update->set('indexed', $update->createNamedParameter(''));
			$update->set('flags', $update->func()->subtract('flags', $update->createNamedParameter(self::FLAG_INDEXED, IQueryBuilder::PARAM_INT)));
			$update->andWhere(
				$update->expr()->eq($update->expr()->castColumn(
					$update->expr()->bitwiseAnd('flags', self::FLAG_INDEXED), IQueryBuilder::PARAM_INT), $update->createNamedParameter(self::FLAG_INDEXED, IQueryBuilder::PARAM_INT)
				));
		}

		$update->executeStatement();

		// we clear all cache
		$this->clearCacheAll();
	}

	/**
	 * @inheritDoc
	 *
	 * @param string $userId id of the user
	 * @param string $app id of the app
	 * @param string $key config key
	 * @param bool $lazy TRUE to set as lazy loaded, FALSE to unset
	 *
	 * @return bool TRUE if entry was found in database and an update was necessary
	 * @since 31.0.0
	 */
	public function updateLazy(string $userId, string $app, string $key, bool $lazy): bool {
		$this->assertParams($userId, $app, $key);
		$this->loadConfigAll($userId);
		$this->matchAndApplyLexiconDefinition($userId, $app, $key);

		try {
			if ($lazy === $this->isLazy($userId, $app, $key)) {
				return false;
			}
		} catch (UnknownKeyException) {
			return false;
		}

		$update = $this->connection->getQueryBuilder();
		$update->update('preferences')
			->set('lazy', $update->createNamedParameter($lazy ? 1 : 0, IQueryBuilder::PARAM_INT))
			->where($update->expr()->eq('userid', $update->createNamedParameter($userId)))
			->andWhere($update->expr()->eq('appid', $update->createNamedParameter($app)))
			->andWhere($update->expr()->eq('configkey', $update->createNamedParameter($key)));
		$update->executeStatement();

		// At this point, it is a lot safer to clean cache
		$this->clearCache($userId);

		return true;
	}

	/**
	 * @inheritDoc
	 *
	 * @param string $app id of the app
	 * @param string $key config key
	 * @param bool $lazy TRUE to set as lazy loaded, FALSE to unset
	 *
	 * @since 31.0.0
	 */
	public function updateGlobalLazy(string $app, string $key, bool $lazy): void {
		$this->assertParams('', $app, $key, allowEmptyUser: true);
		$this->matchAndApplyLexiconDefinition('', $app, $key);

		$update = $this->connection->getQueryBuilder();
		$update->update('preferences')
			->set('lazy', $update->createNamedParameter($lazy ? 1 : 0, IQueryBuilder::PARAM_INT))
			->where($update->expr()->eq('appid', $update->createNamedParameter($app)))
			->andWhere($update->expr()->eq('configkey', $update->createNamedParameter($key)));
		$update->executeStatement();

		$this->clearCacheAll();
	}

	/**
	 * @inheritDoc
	 *
	 * @param string $userId id of the user
	 * @param string $app id of the app
	 * @param string $key config key
	 *
	 * @return array
	 * @throws UnknownKeyException if config key is not known in database
	 * @since 31.0.0
	 */
	public function getDetails(string $userId, string $app, string $key): array {
		$this->assertParams($userId, $app, $key);
		$this->loadConfigAll($userId);
		$this->matchAndApplyLexiconDefinition($userId, $app, $key);

		$lazy = $this->isLazy($userId, $app, $key);

		if ($lazy) {
			$cache = $this->lazyCache[$userId];
		} else {
			$cache = $this->fastCache[$userId];
		}

		$type = $this->getValueType($userId, $app, $key);
		try {
			$typeString = $type->getDefinition();
		} catch (IncorrectTypeException $e) {
			$this->logger->warning('type stored in database is not correct', ['exception' => $e, 'type' => $type]);
			$typeString = (string)$type->value;
		}

		if (!isset($cache[$app][$key])) {
			throw new UnknownKeyException('unknown config key');
		}

		$value = $cache[$app][$key];
		$sensitive = $this->isSensitive($userId, $app, $key, null);
		$this->decryptSensitiveValue($userId, $app, $key, $value);

		return [
			'userId' => $userId,
			'app' => $app,
			'key' => $key,
			'value' => $value,
			'type' => $type->value,
			'lazy' => $lazy,
			'typeString' => $typeString,
			'sensitive' => $sensitive
		];
	}

	/**
	 * @inheritDoc
	 *
	 * @param string $userId id of the user
	 * @param string $app id of the app
	 * @param string $key config key
	 *
	 * @since 31.0.0
	 */
	public function deleteUserConfig(string $userId, string $app, string $key): void {
		$this->assertParams($userId, $app, $key);
		$this->matchAndApplyLexiconDefinition($userId, $app, $key);

		$qb = $this->connection->getQueryBuilder();
		$qb->delete('preferences')
			->where($qb->expr()->eq('userid', $qb->createNamedParameter($userId)))
			->andWhere($qb->expr()->eq('appid', $qb->createNamedParameter($app)))
			->andWhere($qb->expr()->eq('configkey', $qb->createNamedParameter($key)));
		$qb->executeStatement();

		unset($this->lazyCache[$userId][$app][$key]);
		unset($this->fastCache[$userId][$app][$key]);
		unset($this->valueDetails[$userId][$app][$key]);
	}

	/**
	 * @inheritDoc
	 *
	 * @param string $app id of the app
	 * @param string $key config key
	 *
	 * @since 31.0.0
	 */
	public function deleteKey(string $app, string $key): void {
		$this->assertParams('', $app, $key, allowEmptyUser: true);
		$this->matchAndApplyLexiconDefinition('', $app, $key);

		$qb = $this->connection->getQueryBuilder();
		$qb->delete('preferences')
			->where($qb->expr()->eq('appid', $qb->createNamedParameter($app)))
			->andWhere($qb->expr()->eq('configkey', $qb->createNamedParameter($key)));
		$qb->executeStatement();

		$this->clearCacheAll();
	}

	/**
	 * @inheritDoc
	 *
	 * @param string $app id of the app
	 *
	 * @since 31.0.0
	 */
	public function deleteApp(string $app): void {
		$this->assertParams('', $app, allowEmptyUser: true);

		$qb = $this->connection->getQueryBuilder();
		$qb->delete('preferences')
			->where($qb->expr()->eq('appid', $qb->createNamedParameter($app)));
		$qb->executeStatement();

		$this->clearCacheAll();
	}

	public function deleteAllUserConfig(string $userId): void {
		$this->assertParams($userId, '', allowEmptyApp: true);
		$qb = $this->connection->getQueryBuilder();
		$qb->delete('preferences')
			->where($qb->expr()->eq('userid', $qb->createNamedParameter($userId)));
		$qb->executeStatement();

		$this->clearCache($userId);
	}

	/**
	 * @inheritDoc
	 *
	 * @param string $userId id of the user
	 * @param bool $reload set to TRUE to refill cache instantly after clearing it.
	 *
	 * @since 31.0.0
	 */
	public function clearCache(string $userId, bool $reload = false): void {
		$this->assertParams($userId, allowEmptyApp: true);
		$this->lazyLoaded[$userId] = $this->fastLoaded[$userId] = false;
		$this->lazyCache[$userId] = $this->fastCache[$userId] = $this->valueDetails[$userId] = [];

		if (!$reload) {
			return;
		}

		$this->loadConfigAll($userId);
	}

	/**
	 * @inheritDoc
	 *
	 * @since 31.0.0
	 */
	public function clearCacheAll(): void {
		$this->lazyLoaded = $this->fastLoaded = [];
		$this->lazyCache = $this->fastCache = $this->valueDetails = $this->configLexiconDetails = [];
	}

	/**
	 * For debug purpose.
	 * Returns the cached data.
	 *
	 * @return array
	 * @since 31.0.0
	 * @internal
	 */
	public function statusCache(): array {
		return [
			'fastLoaded' => $this->fastLoaded,
			'fastCache' => $this->fastCache,
			'lazyLoaded' => $this->lazyLoaded,
			'lazyCache' => $this->lazyCache,
			'valueDetails' => $this->valueDetails,
		];
	}

	/**
	 * @param int $needle bitflag to search
	 * @param int $flags all flags
	 *
	 * @return bool TRUE if bitflag $needle is set in $flags
	 */
	private function isFlagged(int $needle, int $flags): bool {
		return (($needle & $flags) !== 0);
	}

	/**
	 * Confirm the string set for app and key fit the database description
	 *
	 * @param string $userId
	 * @param string $app assert $app fit in database
	 * @param string $prefKey assert config key fit in database
	 * @param bool $allowEmptyUser
	 * @param bool $allowEmptyApp $app can be empty string
	 * @param ValueType|null $valueType assert value type is only one type
	 */
	private function assertParams(
		string $userId = '',
		string $app = '',
		string $prefKey = '',
		bool $allowEmptyUser = false,
		bool $allowEmptyApp = false,
	): void {
		if (!$allowEmptyUser && $userId === '') {
			throw new InvalidArgumentException('userId cannot be an empty string');
		}
		if (!$allowEmptyApp && $app === '') {
			throw new InvalidArgumentException('app cannot be an empty string');
		}
		if (strlen($userId) > self::USER_MAX_LENGTH) {
			throw new InvalidArgumentException('Value (' . $userId . ') for userId is too long (' . self::USER_MAX_LENGTH . ')');
		}
		if (strlen($app) > self::APP_MAX_LENGTH) {
			throw new InvalidArgumentException('Value (' . $app . ') for app is too long (' . self::APP_MAX_LENGTH . ')');
		}
		if (strlen($prefKey) > self::KEY_MAX_LENGTH) {
			throw new InvalidArgumentException('Value (' . $prefKey . ') for key is too long (' . self::KEY_MAX_LENGTH . ')');
		}
	}

	private function loadConfigAll(string $userId): void {
		$this->loadConfig($userId, null);
	}

	/**
	 * Load normal config or config set as lazy loaded
	 *
	 * @param bool|null $lazy set to TRUE to load config set as lazy loaded, set to NULL to load all config
	 */
	private function loadConfig(string $userId, ?bool $lazy = false): void {
		if ($this->isLoaded($userId, $lazy)) {
			return;
		}

		if (($lazy ?? true) !== false) { // if lazy is null or true, we debug log
			$this->logger->debug('The loading of lazy UserConfig values have been requested', ['exception' => new \RuntimeException('ignorable exception')]);
		}

		$qb = $this->connection->getQueryBuilder();
		$qb->from('preferences');
		$qb->select('appid', 'configkey', 'configvalue', 'type', 'flags');
		$qb->where($qb->expr()->eq('userid', $qb->createNamedParameter($userId)));

		// we only need value from lazy when loadConfig does not specify it
		if ($lazy !== null) {
			$qb->andWhere($qb->expr()->eq('lazy', $qb->createNamedParameter($lazy ? 1 : 0, IQueryBuilder::PARAM_INT)));
		} else {
			$qb->addSelect('lazy');
		}

		$result = $qb->executeQuery();
		$rows = $result->fetchAll();
		foreach ($rows as $row) {
			if (($row['lazy'] ?? ($lazy ?? 0) ? 1 : 0) === 1) {
				$this->lazyCache[$userId][$row['appid']][$row['configkey']] = $row['configvalue'] ?? '';
			} else {
				$this->fastCache[$userId][$row['appid']][$row['configkey']] = $row['configvalue'] ?? '';
			}
			$this->valueDetails[$userId][$row['appid']][$row['configkey']] = ['type' => ValueType::from((int)($row['type'] ?? 0)), 'flags' => (int)$row['flags']];
		}
		$result->closeCursor();
		$this->setAsLoaded($userId, $lazy);
	}

	/**
	 * if $lazy is:
	 *  - false: will returns true if fast config are loaded
	 *  - true : will returns true if lazy config are loaded
	 *  - null : will returns true if both config are loaded
	 *
	 * @param string $userId
	 * @param bool $lazy
	 *
	 * @return bool
	 */
	private function isLoaded(string $userId, ?bool $lazy): bool {
		if ($lazy === null) {
			return ($this->lazyLoaded[$userId] ?? false) && ($this->fastLoaded[$userId] ?? false);
		}

		return $lazy ? $this->lazyLoaded[$userId] ?? false : $this->fastLoaded[$userId] ?? false;
	}

	/**
	 * if $lazy is:
	 * - false: set fast config as loaded
	 * - true : set lazy config as loaded
	 * - null : set both config as loaded
	 *
	 * @param string $userId
	 * @param bool $lazy
	 */
	private function setAsLoaded(string $userId, ?bool $lazy): void {
		if ($lazy === null) {
			$this->fastLoaded[$userId] = $this->lazyLoaded[$userId] = true;
			return;
		}

		// We also create empty entry to keep both fastLoaded/lazyLoaded synced
		if ($lazy) {
			$this->lazyLoaded[$userId] = true;
			$this->fastLoaded[$userId] = $this->fastLoaded[$userId] ?? false;
			$this->fastCache[$userId] = $this->fastCache[$userId] ?? [];
		} else {
			$this->fastLoaded[$userId] = true;
			$this->lazyLoaded[$userId] = $this->lazyLoaded[$userId] ?? false;
			$this->lazyCache[$userId] = $this->lazyCache[$userId] ?? [];
		}
	}

	/**
	 * **Warning:** this will load all lazy values from the database
	 *
	 * @param string $userId id of the user
	 * @param string $app id of the app
	 * @param bool $filtered TRUE to hide sensitive config values. Value are replaced by {@see IConfig::SENSITIVE_VALUE}
	 *
	 * @return array<string, string|int|float|bool|array>
	 */
	private function formatAppValues(string $userId, string $app, array $values, bool $filtered = false): array {
		foreach ($values as $key => $value) {
			//$key = (string)$key;
			try {
				$type = $this->getValueType($userId, $app, (string)$key);
			} catch (UnknownKeyException) {
				continue;
			}

			if ($this->isFlagged(self::FLAG_SENSITIVE, $this->valueDetails[$userId][$app][$key]['flags'] ?? 0)) {
				if ($filtered) {
					$value = IConfig::SENSITIVE_VALUE;
					$type = ValueType::STRING;
				} else {
					$this->decryptSensitiveValue($userId, $app, (string)$key, $value);
				}
			}

			$values[$key] = $this->convertTypedValue($value, $type);
		}

		return $values;
	}

	/**
	 * convert string value to the expected type
	 *
	 * @param string $value
	 * @param ValueType $type
	 *
	 * @return string|int|float|bool|array
	 */
	private function convertTypedValue(string $value, ValueType $type): string|int|float|bool|array {
		switch ($type) {
			case ValueType::INT:
				return (int)$value;
			case ValueType::FLOAT:
				return (float)$value;
			case ValueType::BOOL:
				return in_array(strtolower($value), ['1', 'true', 'yes', 'on']);
			case ValueType::ARRAY:
				try {
					return json_decode($value, true, flags: JSON_THROW_ON_ERROR);
				} catch (JsonException) {
					// ignoreable
				}
				break;
		}
		return $value;
	}


	/**
	 * will change referenced $value with the decrypted value in case of encrypted (sensitive value)
	 *
	 * @param string $userId
	 * @param string $app
	 * @param string $key
	 * @param string $value
	 */
	private function decryptSensitiveValue(string $userId, string $app, string $key, string &$value): void {
		if (!$this->isFlagged(self::FLAG_SENSITIVE, $this->valueDetails[$userId][$app][$key]['flags'] ?? 0)) {
			return;
		}

		if (!str_starts_with($value, self::ENCRYPTION_PREFIX)) {
			return;
		}

		try {
			$value = $this->crypto->decrypt(substr($value, self::ENCRYPTION_PREFIX_LENGTH));
		} catch (\Exception $e) {
			$this->logger->warning('could not decrypt sensitive value', [
				'userId' => $userId,
				'app' => $app,
				'key' => $key,
				'value' => $value,
				'exception' => $e
			]);
		}
	}

	/**
	 * Match and apply current use of config values with defined lexicon.
	 * Set $lazy to NULL only if only interested into checking that $key is alias.
	 *
	 * @throws UnknownKeyException
	 * @throws TypeConflictException
	 * @return bool FALSE if conflict with defined lexicon were observed in the process
	 */
	private function matchAndApplyLexiconDefinition(
		string $userId,
		string $app,
		string &$key,
		?bool &$lazy = null,
		ValueType &$type = ValueType::MIXED,
		int &$flags = 0,
		?string &$default = null,
	): bool {
		$configDetails = $this->getConfigDetailsFromLexicon($app);
		if (array_key_exists($key, $configDetails['aliases']) && !$this->ignoreLexiconAliases) {
			// in case '$rename' is set in ConfigLexiconEntry, we use the new config key
			$key = $configDetails['aliases'][$key];
		}

		if (!array_key_exists($key, $configDetails['entries'])) {
			return $this->applyLexiconStrictness($configDetails['strictness'], $app . '/' . $key);
		}

		// if lazy is NULL, we ignore all check on the type/lazyness/default from Lexicon
		if ($lazy === null) {
			return true;
		}

		/** @var Entry $configValue */
		$configValue = $configDetails['entries'][$key];
		if ($type === ValueType::MIXED) {
			// we overwrite if value was requested as mixed
			$type = $configValue->getValueType();
		} elseif ($configValue->getValueType() !== $type) {
			throw new TypeConflictException('The user config key ' . $app . '/' . $key . ' is typed incorrectly in relation to the config lexicon');
		}

		$lazy = $configValue->isLazy();
		$flags = $configValue->getFlags();
		if ($configValue->isDeprecated()) {
			$this->logger->notice('User config key ' . $app . '/' . $key . ' is set as deprecated.');
		}

		$enforcedValue = $this->config->getSystemValue('lexicon.default.userconfig.enforced', [])[$app][$key] ?? false;
		if (!$enforcedValue && $this->hasKey($userId, $app, $key, $lazy)) {
			// if key exists there should be no need to extract default
			return true;
		}

		// only look for default if needed, default from Lexicon got priority if not overwritten by admin
		if ($default !== null) {
			$default = $this->getSystemDefault($app, $configValue) ?? $configValue->getDefault($this->presetManager->getLexiconPreset()) ?? $default;
		}

		// returning false will make get() returning $default and set() not changing value in database
		return !$enforcedValue;
	}

	/**
	 * get default value set in config/config.php if stored in key:
	 *
	 * 'lexicon.default.userconfig' => [
	 *        <appId> => [
	 *           <configKey> => 'my value',
	 *        ]
	 *     ],
	 *
	 * The entry is converted to string to fit the expected type when managing default value
	 */
	private function getSystemDefault(string $appId, Entry $configValue): ?string {
		$default = $this->config->getSystemValue('lexicon.default.userconfig', [])[$appId][$configValue->getKey()] ?? null;
		if ($default === null) {
			// no system default, using default default.
			return null;
		}

		return $configValue->convertToString($default);
	}

	/**
	 * manage ConfigLexicon behavior based on strictness set in IConfigLexicon
	 *
	 * @param Strictness|null $strictness
	 * @param string $line
	 *
	 * @return bool TRUE if conflict can be fully ignored
	 * @throws UnknownKeyException
	 * @see ILexicon::getStrictness()
	 */
	private function applyLexiconStrictness(?Strictness $strictness, string $configAppKey): bool {
		if ($strictness === null) {
			return true;
		}

		$line = 'The user config key ' . $configAppKey . ' is not defined in the config lexicon';
		switch ($strictness) {
			case Strictness::IGNORE:
				return true;
			case Strictness::NOTICE:
				if (!in_array($configAppKey, $this->strictnessApplied, true)) {
					$this->strictnessApplied[] = $configAppKey;
					$this->logger->notice($line);
				}
				return true;
			case Strictness::WARNING:
				if (!in_array($configAppKey, $this->strictnessApplied, true)) {
					$this->strictnessApplied[] = $configAppKey;
					$this->logger->warning($line);
				}
				return false;
			case Strictness::EXCEPTION:
				throw new UnknownKeyException($line);
		}

		throw new UnknownKeyException($line);
	}

	/**
	 * extract details from registered $appId's config lexicon
	 *
	 * @param string $appId
	 *
	 * @return array{entries: array<string, Entry>, aliases: array<string, string>, strictness: Strictness}
	 * @internal
	 */
	public function getConfigDetailsFromLexicon(string $appId): array {
		if (!array_key_exists($appId, $this->configLexiconDetails)) {
			$entries = $aliases = [];
			$bootstrapCoordinator = \OCP\Server::get(Coordinator::class);
			$configLexicon = $bootstrapCoordinator->getRegistrationContext()?->getConfigLexicon($appId);
			foreach ($configLexicon?->getUserConfigs() ?? [] as $configEntry) {
				$entries[$configEntry->getKey()] = $configEntry;
				if ($configEntry->getRename() !== null) {
					$aliases[$configEntry->getRename()] = $configEntry->getKey();
				}
			}

			$this->configLexiconDetails[$appId] = [
				'entries' => $entries,
				'aliases' => $aliases,
				'strictness' => $configLexicon?->getStrictness() ?? Strictness::IGNORE
			];
		}

		return $this->configLexiconDetails[$appId];
	}

	/**
	 * get Lexicon Entry using appId and config key entry
	 *
	 * @return Entry|null NULL if entry does not exist in user's Lexicon
	 * @internal
	 */
	public function getLexiconEntry(string $appId, string $key): ?Entry {
		return $this->getConfigDetailsFromLexicon($appId)['entries'][$key] ?? null;
	}

	/**
	 * if set to TRUE, ignore aliases defined in Config Lexicon during the use of the methods of this class
	 *
	 * @internal
	 */
	public function ignoreLexiconAliases(bool $ignore): void {
		$this->ignoreLexiconAliases = $ignore;
	}
}
