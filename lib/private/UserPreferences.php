<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC;

use InvalidArgumentException;
use JsonException;
use OCP\DB\Exception as DBException;
use OCP\DB\IResult;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\Security\ICrypto;
use OCP\UserPreferences\Exceptions\IncorrectTypeException;
use OCP\UserPreferences\Exceptions\TypeConflictException;
use OCP\UserPreferences\Exceptions\UnknownKeyException;
use OCP\UserPreferences\IUserPreferences;
use OCP\UserPreferences\ValueType;
use Psr\Log\LoggerInterface;
use ValueError;

/**
 * This class provides an easy way for apps to store user preferences in the
 * database.
 * Supports **lazy loading**
 *
 * ### What is lazy loading ?
 * In order to avoid loading useless user preferences into memory for each request,
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
class UserPreferences implements IUserPreferences {
	private const USER_MAX_LENGTH = 64;
	private const APP_MAX_LENGTH = 32;
	private const KEY_MAX_LENGTH = 64;
	private const ENCRYPTION_PREFIX = '$UserPreferencesEncryption$';
	private const ENCRYPTION_PREFIX_LENGTH = 27; // strlen(self::ENCRYPTION_PREFIX)

	/** @var array<string, array<string, array<string, mixed>>> ['user_id' => ['app_id' => ['key' => 'value']]] */
	private array $fastCache = [];   // cache for normal preference keys
	/** @var array<string, array<string, array<string, mixed>>> ['user_id' => ['app_id' => ['key' => 'value']]] */
	private array $lazyCache = [];   // cache for lazy preference keys
	/** @var array<string, array<string, array<string, int>>> ['user_id' => ['app_id' => ['key' => bitflag]]] */
	private array $valueTypes = [];  // type for all preference values
	/** @var array<string, boolean> ['user_id' => bool] */
	private array $fastLoaded = [];
	/** @var array<string, boolean> ['user_id' => bool] */
	private array $lazyLoaded = [];

	public function __construct(
		protected IDBConnection $connection,
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
		$this->loadPreferencesAll($userId);
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
	 * @return list<string> list of stored preference keys
	 * @since 31.0.0
	 */
	public function getKeys(string $userId, string $app): array {
		$this->assertParams($userId, $app);
		$this->loadPreferencesAll($userId);
		// array_merge() will remove numeric keys (here preference keys), so addition arrays instead
		$keys = array_map('strval', array_keys(($this->fastCache[$userId][$app] ?? []) + ($this->lazyCache[$userId][$app] ?? [])));
		sort($keys);

		return array_values(array_unique($keys));
	}

	/**
	 * @inheritDoc
	 *
	 * @param string $userId id of the user
	 * @param string $app id of the app
	 * @param string $key preference key
	 * @param bool|null $lazy TRUE to search within lazy loaded preferences, NULL to search within all preferences
	 *
	 * @return bool TRUE if key exists
	 * @since 31.0.0
	 */
	public function hasKey(string $userId, string $app, string $key, ?bool $lazy = false): bool {
		$this->assertParams($userId, $app, $key);
		$this->loadPreferences($userId, $lazy);

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
	 * @param string $userId id of the user
	 * @param string $app id of the app
	 * @param string $key preference key
	 * @param bool|null $lazy TRUE to search within lazy loaded preferences, NULL to search within all preferences
	 *
	 * @return bool
	 * @throws UnknownKeyException if preference key is not known
	 * @since 29.0.0
	 */
	public function isSensitive(string $userId, string $app, string $key, ?bool $lazy = false): bool {
		$this->assertParams($userId, $app, $key);
		$this->loadPreferences($userId, $lazy);

		if (!isset($this->valueTypes[$userId][$app][$key])) {
			throw new UnknownKeyException('unknown preference key');
		}

		return $this->isTyped(ValueType::SENSITIVE, $this->valueTypes[$userId][$app][$key]);
	}

	/**
	 * @inheritDoc
	 *
	 * @param string $userId id of the user
	 * @param string $app if of the app
	 * @param string $key preference key
	 *
	 * @return bool TRUE if preference is lazy loaded
	 * @throws UnknownKeyException if preference key is not known
	 * @see IUserPreferences for details about lazy loading
	 * @since 29.0.0
	 */
	public function isLazy(string $userId, string $app, string $key): bool {
		// there is a huge probability the non-lazy preferences are already loaded
		if ($this->hasKey($userId, $app, $key, false)) {
			return false;
		}

		// key not found, we search in the lazy preferences
		if ($this->hasKey($userId, $app, $key, true)) {
			return true;
		}

		throw new UnknownKeyException('unknown preference key');
	}

	/**
	 * @inheritDoc
	 *
	 * @param string $userId id of the user
	 * @param string $app id of the app
	 * @param string $prefix preference keys prefix to search
	 * @param bool $filtered TRUE to hide sensitive preference values. Value are replaced by {@see IConfig::SENSITIVE_VALUE}
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
		$this->loadPreferencesAll($userId);
		// array_merge() will remove numeric keys (here preference keys), so addition arrays instead
		$values = array_filter(
			$this->formatAppValues($userId, $app, ($this->fastCache[$userId][$app] ?? []) + ($this->lazyCache[$userId][$app] ?? []), $filtered),
			function (string $key) use ($prefix): bool {
				return str_starts_with($key, $prefix); // filter values based on $prefix
			}, ARRAY_FILTER_USE_KEY
		);

		return $values;
	}

	/**
	 * @inheritDoc
	 *
	 * @param string $userId id of the user
	 * @param bool $filtered TRUE to hide sensitive preference values. Value are replaced by {@see IConfig::SENSITIVE_VALUE}
	 *
	 * @return array<string, array<string, string|int|float|bool|array>> [appId => [key => value]]
	 * @since 31.0.0
	 */
	public function getAllValues(string $userId, bool $filtered = false): array {
		$this->assertParams($userId, allowEmptyApp: true);
		$this->loadPreferencesAll($userId);

		$result = [];
		foreach ($this->getApps($userId) as $app) {
			// array_merge() will remove numeric keys (here preference keys), so addition arrays instead
			$cached = ($this->fastCache[$userId][$app] ?? []) + ($this->lazyCache[$userId][$app] ?? []);
			$result[$app] = $this->formatAppValues($userId, $app, $cached, $filtered);
		}

		return $result;
	}

	/**
	 * @inheritDoc
	 *
	 * @param string $userId id of the user
	 * @param string $key preference key
	 * @param bool $lazy search within lazy loaded preferences
	 * @param ValueType|null $typedAs enforce type for the returned values
	 *
	 * @return array<string, string|int|float|bool|array> [appId => value]
	 * @since 31.0.0
	 */
	public function searchValuesByApps(string $userId, string $key, bool $lazy = false, ?ValueType $typedAs = null): array {
		$this->assertParams($userId, '', $key, allowEmptyApp: true);
		$this->loadPreferences($userId, $lazy);

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
	 * @param string $key preference key
	 * @param ValueType|null $typedAs enforce type for the returned values
	 * @param array|null $userIds limit to a list of user ids
	 *
	 * @return array<string, string|int|float|bool|array> [userId => value]
	 * @since 31.0.0
	 */
	public function searchValuesByUsers(
		string $app,
		string $key,
		?ValueType $typedAs = null,
		?array $userIds = null,
	): array {
		$this->assertParams('', $app, $key, allowEmptyUser: true);

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
					$value = $this->convertTypedValue($value, $typedAs ?? $this->extractValueType($row['type']));
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
	 * @param string $key preference key
	 * @param string $value preference value
	 * @param bool $caseInsensitive non-case-sensitive search, only works if $value is a string
	 *
	 * @return list<string>
	 * @since 31.0.0
	 */
	public function searchUsersByValueString(string $app, string $key, string $value, bool $caseInsensitive = false): array {
		return $this->searchUsersByTypedValue($app, $key, $value, $caseInsensitive);
	}

	/**
	 * @inheritDoc
	 *
	 * @param string $app id of the app
	 * @param string $key preference key
	 * @param int $value preference value
	 *
	 * @return list<string>
	 * @since 31.0.0
	 */
	public function searchUsersByValueInt(string $app, string $key, int $value): array {
		return $this->searchUsersByValueString($app, $key, (string)$value);
	}

	/**
	 * @inheritDoc
	 *
	 * @param string $app id of the app
	 * @param string $key preference key
	 * @param array $values list of preference values
	 *
	 * @return list<string>
	 * @since 31.0.0
	 */
	public function searchUsersByValues(string $app, string $key, array $values): array {
		return $this->searchUsersByTypedValue($app, $key, $values);
	}

	/**
	 * @inheritDoc
	 *
	 * @param string $app id of the app
	 * @param string $key preference key
	 * @param bool $value preference value
	 *
	 * @return list<string>
	 * @since 31.0.0
	 */
	public function searchUsersByValueBool(string $app, string $key, bool $value): array {
		$values = ['0', 'off', 'false', 'no'];
		if ($value) {
			$values = ['1', 'on', 'true', 'yes'];
		}
		return $this->searchUsersByValues($app, $key, $values);
	}

	/**
	 * returns a list of users with preference key set to a specific value, or within the list of
	 * possible values
	 *
	 * @param string $app
	 * @param string $key
	 * @param string|array $value
	 * @param bool $caseInsensitive
	 *
	 * @return list<string>
	 */
	private function searchUsersByTypedValue(string $app, string $key, string|array $value, bool $caseInsensitive = false): array {
		$this->assertParams('', $app, $key, allowEmptyUser: true);

		$qb = $this->connection->getQueryBuilder();
		$qb->from('preferences');
		$qb->select('userid');
		$qb->where($qb->expr()->eq('appid', $qb->createNamedParameter($app)));
		$qb->andWhere($qb->expr()->eq('configkey', $qb->createNamedParameter($key)));

		$configValueColumn = ($this->connection->getDatabaseProvider() === IDBConnection::PLATFORM_ORACLE) ? $qb->expr()->castColumn('configvalue', IQueryBuilder::PARAM_STR) : 'configvalue';
		if (is_array($value)) {
			$qb->andWhere($qb->expr()->in($configValueColumn, $qb->createNamedParameter($value, IQueryBuilder::PARAM_STR_ARRAY)));
		} else {
			if ($caseInsensitive) {
				$qb->andWhere($qb->expr()->eq(
					$qb->func()->lower($configValueColumn),
					$qb->createNamedParameter(strtolower($value)))
				);
			} else {
				$qb->andWhere($qb->expr()->eq($configValueColumn, $qb->createNamedParameter($value)));
			}
		}

		$userIds = [];
		$result = $qb->executeQuery();
		$rows = $result->fetchAll();
		foreach ($rows as $row) {
			$userIds[] = $row['userid'];
		}

		return $userIds;
	}

	/**
	 * Get the preference value as string.
	 * If the value does not exist the given default will be returned.
	 *
	 * Set lazy to `null` to ignore it and get the value from either source.
	 *
	 * **WARNING:** Method is internal and **SHOULD** not be used, as it is better to get the value with a type.
	 *
	 * @param string $userId id of the user
	 * @param string $app id of the app
	 * @param string $key preference key
	 * @param string $default preference value
	 * @param null|bool $lazy get preference as lazy loaded or not. can be NULL
	 *
	 * @return string the value or $default
	 * @throws TypeConflictException
	 * @internal
	 * @since 31.0.0
	 * @see IUserPreferences for explanation about lazy loading
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
		try {
			$lazy = ($lazy === null) ? $this->isLazy($userId, $app, $key) : $lazy;
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
	 * @param string $key preference key
	 * @param string $default default value
	 * @param bool $lazy search within lazy loaded preferences
	 *
	 * @return string stored preference value or $default if not set in database
	 * @throws InvalidArgumentException if one of the argument format is invalid
	 * @throws TypeConflictException in case of conflict with the value type set in database
	 * @since 31.0.0
	 * @see IUserPreferences for explanation about lazy loading
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
	 * @param string $key preference key
	 * @param int $default default value
	 * @param bool $lazy search within lazy loaded preferences
	 *
	 * @return int stored preference value or $default if not set in database
	 * @throws InvalidArgumentException if one of the argument format is invalid
	 * @throws TypeConflictException in case of conflict with the value type set in database
	 * @since 31.0.0
	 * @see IUserPreferences for explanation about lazy loading
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
	 * @param string $key preference key
	 * @param float $default default value
	 * @param bool $lazy search within lazy loaded preferences
	 *
	 * @return float stored preference value or $default if not set in database
	 * @throws InvalidArgumentException if one of the argument format is invalid
	 * @throws TypeConflictException in case of conflict with the value type set in database
	 * @since 29.0.0
	 * @see IUserPreferences for explanation about lazy loading
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
	 * @param string $key preference key
	 * @param bool $default default value
	 * @param bool $lazy search within lazy loaded preferences
	 *
	 * @return bool stored preference value or $default if not set in database
	 * @throws InvalidArgumentException if one of the argument format is invalid
	 * @throws TypeConflictException in case of conflict with the value type set in database
	 * @since 29.0.0
	 * @see IUserPreferences for explanation about lazy loading
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
	 * @param string $key preference key
	 * @param array $default default value
	 * @param bool $lazy search within lazy loaded preferences
	 *
	 * @return array stored preference value or $default if not set in database
	 * @throws InvalidArgumentException if one of the argument format is invalid
	 * @throws TypeConflictException in case of conflict with the value type set in database
	 * @since 29.0.0
	 * @see IUserPreferences for explanation about lazy loading
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
	 * @param string $key preference key
	 * @param string $default default value
	 * @param bool $lazy search within lazy loaded preferences
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
		$this->assertParams($userId, $app, $key, valueType: $type);
		$this->loadPreferences($userId, $lazy);

		/**
		 * We ignore check if mixed type is requested.
		 * If type of stored value is set as mixed, we don't filter.
		 * If type of stored value is defined, we compare with the one requested.
		 */
		$knownType = $this->valueTypes[$userId][$app][$key] ?? 0;
		if (!$this->isTyped(ValueType::MIXED, $type->value)
			&& $knownType > 0
			&& !$this->isTyped(ValueType::MIXED, $knownType)
			&& !$this->isTyped($type, $knownType)) {
			$this->logger->warning('conflict with value type from database', ['app' => $app, 'key' => $key, 'type' => $type, 'knownType' => $knownType]);
			throw new TypeConflictException('conflict with value type from database');
		}

		/**
		 * - the pair $app/$key cannot exist in both array,
		 * - we should still return an existing non-lazy value even if current method
		 *   is called with $lazy is true
		 *
		 * This way, lazyCache will be empty until the load for lazy preferences value is requested.
		 */
		if (isset($this->lazyCache[$userId][$app][$key])) {
			$value = $this->lazyCache[$userId][$app][$key];
		} elseif (isset($this->fastCache[$userId][$app][$key])) {
			$value = $this->fastCache[$userId][$app][$key];
		} else {
			return $default;
		}

		$this->decryptSensitiveValue($userId, $app, $key, $value);
		return $value;
	}

	/**
	 * @inheritDoc
	 *
	 * @param string $userId id of the user
	 * @param string $app id of the app
	 * @param string $key preference key
	 *
	 * @return ValueType type of the value
	 * @throws UnknownKeyException if preference key is not known
	 * @throws IncorrectTypeException if preferences value type is not known
	 * @since 31.0.0
	 */
	public function getValueType(string $userId, string $app, string $key, ?bool $lazy = null): ValueType {
		$this->assertParams($userId, $app, $key);
		$this->loadPreferences($userId, $lazy);

		if (!isset($this->valueTypes[$userId][$app][$key])) {
			throw new UnknownKeyException('unknown preference key');
		}

		return $this->extractValueType($this->valueTypes[$userId][$app][$key]);
	}

	/**
	 * convert bitflag from value type to ValueType
	 *
	 * @param int $type
	 *
	 * @return ValueType
	 * @throws IncorrectTypeException
	 */
	private function extractValueType(int $type): ValueType {
		$type &= ~ValueType::SENSITIVE->value;

		try {
			return ValueType::from($type);
		} catch (ValueError) {
			throw new IncorrectTypeException('invalid value type');
		}
	}

	/**
	 * Store a preference key and its value in database as VALUE_MIXED
	 *
	 * **WARNING:** Method is internal and **MUST** not be used as it is best to set a real value type
	 *
	 * @param string $userId id of the user
	 * @param string $app id of the app
	 * @param string $key preference key
	 * @param string $value preference value
	 * @param bool $lazy set preference as lazy loaded
	 * @param bool $sensitive if TRUE value will be hidden when listing preference values.
	 *
	 * @return bool TRUE if value was different, therefor updated in database
	 * @throws TypeConflictException if type from database is not VALUE_MIXED
	 * @internal
	 * @since 29.0.0
	 * @see IUserPreferences for explanation about lazy loading
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
		bool $sensitive = false,
	): bool {
		return $this->setTypedValue(
			$userId,
			$app,
			$key,
			$value,
			$lazy,
			$sensitive,
			ValueType::MIXED
		);
	}


	/**
	 * @inheritDoc
	 *
	 * @param string $userId id of the user
	 * @param string $app id of the app
	 * @param string $key preference key
	 * @param string $value preference value
	 * @param bool $lazy set preference as lazy loaded
	 * @param bool $sensitive if TRUE value will be hidden when listing preference values.
	 *
	 * @return bool TRUE if value was different, therefor updated in database
	 * @throws TypeConflictException if type from database is not VALUE_MIXED and different from the requested one
	 * @since 29.0.0
	 * @see IUserPreferences for explanation about lazy loading
	 */
	public function setValueString(
		string $userId,
		string $app,
		string $key,
		string $value,
		bool $lazy = false,
		bool $sensitive = false,
	): bool {
		return $this->setTypedValue(
			$userId,
			$app,
			$key,
			$value,
			$lazy,
			$sensitive,
			ValueType::STRING
		);
	}

	/**
	 * @inheritDoc
	 *
	 * @param string $userId id of the user
	 * @param string $app id of the app
	 * @param string $key preference key
	 * @param int $value preference value
	 * @param bool $lazy set preference as lazy loaded
	 * @param bool $sensitive if TRUE value will be hidden when listing preference values.
	 *
	 * @return bool TRUE if value was different, therefor updated in database
	 * @throws TypeConflictException if type from database is not VALUE_MIXED and different from the requested one
	 * @since 29.0.0
	 * @see IUserPreferences for explanation about lazy loading
	 */
	public function setValueInt(
		string $userId,
		string $app,
		string $key,
		int $value,
		bool $lazy = false,
		bool $sensitive = false,
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
			$sensitive,
			ValueType::INT
		);
	}

	/**
	 * @inheritDoc
	 *
	 * @param string $userId id of the user
	 * @param string $app id of the app
	 * @param string $key preference key
	 * @param float $value preference value
	 * @param bool $lazy set preference as lazy loaded
	 * @param bool $sensitive if TRUE value will be hidden when listing preference values.
	 *
	 * @return bool TRUE if value was different, therefor updated in database
	 * @throws TypeConflictException if type from database is not VALUE_MIXED and different from the requested one
	 * @since 29.0.0
	 * @see IUserPreferences for explanation about lazy loading
	 */
	public function setValueFloat(
		string $userId,
		string $app,
		string $key,
		float $value,
		bool $lazy = false,
		bool $sensitive = false,
	): bool {
		return $this->setTypedValue(
			$userId,
			$app,
			$key,
			(string)$value,
			$lazy,
			$sensitive,
			ValueType::FLOAT
		);
	}

	/**
	 * @inheritDoc
	 *
	 * @param string $userId id of the user
	 * @param string $app id of the app
	 * @param string $key preference key
	 * @param bool $value preference value
	 * @param bool $lazy set preference as lazy loaded
	 *
	 * @return bool TRUE if value was different, therefor updated in database
	 * @throws TypeConflictException if type from database is not VALUE_MIXED and different from the requested one
	 * @since 29.0.0
	 * @see IUserPreferences for explanation about lazy loading
	 */
	public function setValueBool(
		string $userId,
		string $app,
		string $key,
		bool $value,
		bool $lazy = false,
	): bool {
		return $this->setTypedValue(
			$userId,
			$app,
			$key,
			($value) ? '1' : '0',
			$lazy,
			false,
			ValueType::BOOL
		);
	}

	/**
	 * @inheritDoc
	 *
	 * @param string $userId id of the user
	 * @param string $app id of the app
	 * @param string $key preference key
	 * @param array $value preference value
	 * @param bool $lazy set preference as lazy loaded
	 * @param bool $sensitive if TRUE value will be hidden when listing preference values.
	 *
	 * @return bool TRUE if value was different, therefor updated in database
	 * @throws TypeConflictException if type from database is not VALUE_MIXED and different from the requested one
	 * @throws JsonException
	 * @since 29.0.0
	 * @see IUserPreferences for explanation about lazy loading
	 */
	public function setValueArray(
		string $userId,
		string $app,
		string $key,
		array $value,
		bool $lazy = false,
		bool $sensitive = false,
	): bool {
		try {
			return $this->setTypedValue(
				$userId,
				$app,
				$key,
				json_encode($value, JSON_THROW_ON_ERROR),
				$lazy,
				$sensitive,
				ValueType::ARRAY
			);
		} catch (JsonException $e) {
			$this->logger->warning('could not setValueArray', ['app' => $app, 'key' => $key, 'exception' => $e]);
			throw $e;
		}
	}

	/**
	 * Store a preference key and its value in database
	 *
	 * If preference key is already known with the exact same preference value and same sensitive/lazy status, the
	 * database is not updated. If preference value was previously stored as sensitive, status will not be
	 * altered.
	 *
	 * @param string $userId id of the user
	 * @param string $app id of the app
	 * @param string $key preference key
	 * @param string $value preference value
	 * @param bool $lazy preferences set as lazy loaded
	 * @param ValueType $type value type
	 *
	 * @return bool TRUE if value was updated in database
	 * @throws TypeConflictException if type from database is not VALUE_MIXED and different from the requested one
	 * @see IUserPreferences for explanation about lazy loading
	 */
	private function setTypedValue(
		string $userId,
		string $app,
		string $key,
		string $value,
		bool $lazy,
		bool $sensitive,
		ValueType $type,
	): bool {
		$this->assertParams($userId, $app, $key, valueType: $type);
		$this->loadPreferences($userId, $lazy);

		$inserted = $refreshCache = false;
		$origValue = $value;
		$typeValue = $type->value;
		if ($sensitive || ($this->hasKey($userId, $app, $key, $lazy) && $this->isSensitive($userId, $app, $key, $lazy))) {
			$value = self::ENCRYPTION_PREFIX . $this->crypto->encrypt($value);
			$typeValue = $typeValue | ValueType::SENSITIVE->value;
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
					->setValue('type', $insert->createNamedParameter($typeValue, IQueryBuilder::PARAM_INT))
					->setValue('configkey', $insert->createNamedParameter($key))
					->setValue('configvalue', $insert->createNamedParameter($value));
				$insert->executeStatement();
				$inserted = true;
			} catch (DBException $e) {
				if ($e->getReason() !== DBException::REASON_UNIQUE_CONSTRAINT_VIOLATION) {
					throw $e; // TODO: throw exception or just log and returns false !?
				}
			}
		}

		/**
		 * We cannot insert a new row, meaning we need to update an already existing one
		 */
		if (!$inserted) {
			$currType = $this->valueTypes[$userId][$app][$key] ?? 0;
			if ($currType === 0) { // this might happen when switching lazy loading status
				$this->loadPreferencesAll($userId);
				$currType = $this->valueTypes[$userId][$app][$key] ?? 0;
			}

			/**
			 * This should only happen during the upgrade process from 28 to 29.
			 * We only log a warning and set it to VALUE_MIXED.
			 */
			if ($currType === 0) {
				$this->logger->warning('Value type is set to zero (0) in database. This is fine only during the upgrade process from 28 to 29.', ['app' => $app, 'key' => $key]);
				$currType = ValueType::MIXED->value;
			}

			//			if ($type->isSensitive()) {}

			/**
			 * we only accept a different type from the one stored in database
			 * if the one stored in database is not-defined (VALUE_MIXED)
			 */
			if (!$this->isTyped(ValueType::MIXED, $currType) &&
				($type->value | ValueType::SENSITIVE->value) !== ($currType | ValueType::SENSITIVE->value)) {
				try {
					$currType = $this->extractValueType($currType)->getDefinition();
					$type = $type->getDefinition();
				} catch (IncorrectTypeException) {
					$type = $type->value;
				}
				throw new TypeConflictException('conflict between new type (' . $type . ') and old type (' . $currType . ')');
			}

			if ($lazy !== $this->isLazy($userId, $app, $key)) {
				$refreshCache = true;
			}

			$update = $this->connection->getQueryBuilder();
			$update->update('preferences')
				->set('configvalue', $update->createNamedParameter($value))
				->set('lazy', $update->createNamedParameter(($lazy) ? 1 : 0, IQueryBuilder::PARAM_INT))
				->set('type', $update->createNamedParameter($typeValue, IQueryBuilder::PARAM_INT))
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
		$this->valueTypes[$userId][$app][$key] = $typeValue;

		return true;
	}

	/**
	 * Change the type of preference value.
	 *
	 * **WARNING:** Method is internal and **MUST** not be used as it may break things.
	 *
	 * @param string $userId id of the user
	 * @param string $app id of the app
	 * @param string $key preference key
	 * @param ValueType $type value type
	 *
	 * @return bool TRUE if database update were necessary
	 * @throws UnknownKeyException if $key is now known in database
	 * @throws IncorrectTypeException if $type is not valid
	 * @internal
	 * @since 31.0.0
	 */
	public function updateType(string $userId, string $app, string $key, ValueType $type = ValueType::MIXED): bool {
		$this->assertParams($userId, $app, $key, valueType: $type);
		$this->loadPreferencesAll($userId);
		$this->isLazy($userId, $app, $key); // confirm key exists
		$typeValue = $type->value;

		$currType = $this->valueTypes[$userId][$app][$key];
		if (($typeValue | ValueType::SENSITIVE->value) === ($currType | ValueType::SENSITIVE->value)) {
			return false;
		}

		// we complete with sensitive flag if the stored value is set as sensitive
		if ($this->isTyped(ValueType::SENSITIVE, $currType)) {
			$typeValue = $typeValue | ValueType::SENSITIVE->value;
		}

		$update = $this->connection->getQueryBuilder();
		$update->update('preferences')
			->set('type', $update->createNamedParameter($typeValue, IQueryBuilder::PARAM_INT))
			->where($update->expr()->eq('userid', $update->createNamedParameter($userId)))
			->andWhere($update->expr()->eq('appid', $update->createNamedParameter($app)))
			->andWhere($update->expr()->eq('configkey', $update->createNamedParameter($key)));
		$update->executeStatement();
		$this->valueTypes[$userId][$app][$key] = $typeValue;

		return true;
	}

	/**
	 * @inheritDoc
	 *
	 * @param string $userId id of the user
	 * @param string $app id of the app
	 * @param string $key preference key
	 * @param bool $sensitive TRUE to set as sensitive, FALSE to unset
	 *
	 * @return bool TRUE if entry was found in database and an update was necessary
	 * @since 31.0.0
	 */
	public function updateSensitive(string $userId, string $app, string $key, bool $sensitive): bool {
		$this->assertParams($userId, $app, $key);
		$this->loadPreferencesAll($userId);

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
			throw new UnknownKeyException('unknown preference key');
		}

		/**
		 * type returned by getValueType() is already cleaned from sensitive flag
		 * we just need to update it based on $sensitive and store it in database
		 */
		$typeValue = $this->getValueType($userId, $app, $key)->value;
		$value = $cache[$userId][$app][$key];
		if ($sensitive) {
			$typeValue |= ValueType::SENSITIVE->value;
			$value = self::ENCRYPTION_PREFIX . $this->crypto->encrypt($value);
		} else {
			$this->decryptSensitiveValue($userId, $app, $key, $value);
		}

		$update = $this->connection->getQueryBuilder();
		$update->update('preferences')
			->set('type', $update->createNamedParameter($typeValue, IQueryBuilder::PARAM_INT))
			->set('configvalue', $update->createNamedParameter($value))
			->where($update->expr()->eq('userid', $update->createNamedParameter($userId)))
			->andWhere($update->expr()->eq('appid', $update->createNamedParameter($app)))
			->andWhere($update->expr()->eq('configkey', $update->createNamedParameter($key)));
		$update->executeStatement();

		$this->valueTypes[$userId][$app][$key] = $typeValue;

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
		foreach (array_keys($this->searchValuesByUsers($app, $key)) as $userId) {
			try {
				$this->updateSensitive($userId, $app, $key, $sensitive);
			} catch (UnknownKeyException) {
				// should not happen and can be ignored
			}
		}

		$this->clearCacheAll(); // we clear all cache
	}

	/**
	 * @inheritDoc
	 *
	 * @param string $userId id of the user
	 * @param string $app id of the app
	 * @param string $key preference key
	 * @param bool $lazy TRUE to set as lazy loaded, FALSE to unset
	 *
	 * @return bool TRUE if entry was found in database and an update was necessary
	 * @since 31.0.0
	 */
	public function updateLazy(string $userId, string $app, string $key, bool $lazy): bool {
		$this->assertParams($userId, $app, $key);
		$this->loadPreferencesAll($userId);

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
	 * @param string $key preference key
	 * @param bool $lazy TRUE to set as lazy loaded, FALSE to unset
	 *
	 * @since 31.0.0
	 */
	public function updateGlobalLazy(string $app, string $key, bool $lazy): void {
		$this->assertParams('', $app, $key, allowEmptyUser: true);

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
	 * @param string $key preference key
	 *
	 * @return array
	 * @throws UnknownKeyException if preference key is not known in database
	 * @since 31.0.0
	 */
	public function getDetails(string $userId, string $app, string $key): array {
		$this->assertParams($userId, $app, $key);
		$this->loadPreferencesAll($userId);
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
			throw new UnknownKeyException('unknown preference key');
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
	 * @param string $key preference key
	 *
	 * @since 31.0.0
	 */
	public function deletePreference(string $userId, string $app, string $key): void {
		$this->assertParams($userId, $app, $key);
		$qb = $this->connection->getQueryBuilder();
		$qb->delete('preferences')
			->where($qb->expr()->eq('userid', $qb->createNamedParameter($userId)))
			->andWhere($qb->expr()->eq('appid', $qb->createNamedParameter($app)))
			->andWhere($qb->expr()->eq('configkey', $qb->createNamedParameter($key)));
		$qb->executeStatement();

		unset($this->lazyCache[$userId][$app][$key]);
		unset($this->fastCache[$userId][$app][$key]);
	}

	/**
	 * @inheritDoc
	 *
	 * @param string $app id of the app
	 * @param string $key preference key
	 *
	 * @since 31.0.0
	 */
	public function deleteKey(string $app, string $key): void {
		$this->assertParams('', $app, $key, allowEmptyUser: true);
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

	public function deleteAllPreferences(string $userId): void {
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
		$this->lazyCache[$userId] = $this->fastCache[$userId] = $this->valueTypes[$userId] = [];

		if (!$reload) {
			return;
		}

		$this->loadPreferencesAll($userId);
	}

	/**
	 * @inheritDoc
	 *
	 * @since 31.0.0
	 */
	public function clearCacheAll(): void {
		$this->lazyLoaded = $this->fastLoaded = [];
		$this->lazyCache = $this->fastCache = $this->valueTypes = [];
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
			'valueTypes' => $this->valueTypes,
		];
	}

	/**
	 * @param ValueType $needle bitflag to search
	 * @param int $type known value
	 *
	 * @return bool TRUE if bitflag $needle is set in $type
	 */
	private function isTyped(ValueType $needle, int $type): bool {
		return (($needle->value & $type) !== 0);
	}

	/**
	 * Confirm the string set for app and key fit the database description
	 *
	 * @param string $userId
	 * @param string $app assert $app fit in database
	 * @param string $prefKey assert preference key fit in database
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
		?ValueType $valueType = null,
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
		if ($valueType !== null) {
			$valueFlag = $valueType->value;
			$valueFlag &= ~ValueType::SENSITIVE->value;
			if (ValueType::tryFrom($valueFlag) === null) {
				throw new InvalidArgumentException('Unknown value type');
			}
		}
	}

	private function loadPreferencesAll(string $userId): void {
		$this->loadPreferences($userId, null);
	}

	/**
	 * Load normal preferences or preferences set as lazy loaded
	 *
	 * @param bool|null $lazy set to TRUE to load preferences set as lazy loaded, set to NULL to load all preferences
	 */
	private function loadPreferences(string $userId, ?bool $lazy = false): void {
		if ($this->isLoaded($userId, $lazy)) {
			return;
		}

		if (($lazy ?? true) !== false) { // if lazy is null or true, we debug log
			$this->logger->debug('The loading of lazy UserPreferences values have been requested', ['exception' => new \RuntimeException('ignorable exception')]);
		}

		$qb = $this->connection->getQueryBuilder();
		$qb->from('preferences');
		$qb->select('userid', 'appid', 'configkey', 'configvalue', 'type');
		$qb->where($qb->expr()->eq('userid', $qb->createNamedParameter($userId)));

		// we only need value from lazy when loadPreferences does not specify it
		if ($lazy !== null) {
			$qb->andWhere($qb->expr()->eq('lazy', $qb->createNamedParameter($lazy ? 1 : 0, IQueryBuilder::PARAM_INT)));
		} else {
			$qb->addSelect('lazy');
		}

		$result = $qb->executeQuery();
		$rows = $result->fetchAll();
		foreach ($rows as $row) {
			if (($row['lazy'] ?? ($lazy ?? 0) ? 1 : 0) === 1) {
				$this->lazyCache[$row['userid']][$row['appid']][$row['configkey']] = $row['configvalue'] ?? '';
			} else {
				$this->fastCache[$row['userid']][$row['appid']][$row['configkey']] = $row['configvalue'] ?? '';
			}
			$this->valueTypes[$row['userid']][$row['appid']][$row['configkey']] = (int)($row['type'] ?? 0);
		}
		$result->closeCursor();
		$this->setAsLoaded($userId, $lazy);
	}

	/**
	 * if $lazy is:
	 *  - false: will returns true if fast preferences are loaded
	 *  - true : will returns true if lazy preferences are loaded
	 *  - null : will returns true if both preferences are loaded
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
	 * - false: set fast preferences as loaded
	 * - true : set lazy preferences as loaded
	 * - null : set both preferences as loaded
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
	 * @param bool $filtered TRUE to hide sensitive preference values. Value are replaced by {@see IConfig::SENSITIVE_VALUE}
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

			if ($this->isTyped(ValueType::SENSITIVE, $this->valueTypes[$userId][$app][$key] ?? 0)) {
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


	private function decryptSensitiveValue(string $userId, string $app, string $key, string &$value): void {
		if (!$this->isTyped(ValueType::SENSITIVE, $this->valueTypes[$userId][$app][$key] ?? 0)) {
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
}
