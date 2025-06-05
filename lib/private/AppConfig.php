<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OC;

use InvalidArgumentException;
use JsonException;
use NCU\Config\Lexicon\ConfigLexiconEntry;
use NCU\Config\Lexicon\ConfigLexiconStrictness;
use NCU\Config\Lexicon\IConfigLexicon;
use OC\AppFramework\Bootstrap\Coordinator;
use OCP\DB\Exception as DBException;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\Exceptions\AppConfigIncorrectTypeException;
use OCP\Exceptions\AppConfigTypeConflictException;
use OCP\Exceptions\AppConfigUnknownKeyException;
use OCP\IAppConfig;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\Security\ICrypto;
use Psr\Log\LoggerInterface;

/**
 * This class provides an easy way for apps to store config values in the
 * database.
 *
 * **Note:** since 29.0.0, it supports **lazy loading**
 *
 * ### What is lazy loading ?
 * In order to avoid loading useless config values into memory for each request,
 * only non-lazy values are now loaded.
 *
 * Once a value that is lazy is requested, all lazy values will be loaded.
 *
 * Similarly, some methods from this class are marked with a warning about ignoring
 * lazy loading. Use them wisely and only on parts of the code that are called
 * during specific requests or actions to avoid loading the lazy values all the time.
 *
 * @since 7.0.0
 * @since 29.0.0 - Supporting types and lazy loading
 */
class AppConfig implements IAppConfig {
	private const APP_MAX_LENGTH = 32;
	private const KEY_MAX_LENGTH = 64;
	private const ENCRYPTION_PREFIX = '$AppConfigEncryption$';
	private const ENCRYPTION_PREFIX_LENGTH = 21; // strlen(self::ENCRYPTION_PREFIX)

	/** @var array<string, array<string, mixed>> ['app_id' => ['config_key' => 'config_value']] */
	private array $fastCache = [];   // cache for normal config keys
	/** @var array<string, array<string, mixed>> ['app_id' => ['config_key' => 'config_value']] */
	private array $lazyCache = [];   // cache for lazy config keys
	/** @var array<string, array<string, int>> ['app_id' => ['config_key' => bitflag]] */
	private array $valueTypes = [];  // type for all config values
	private bool $fastLoaded = false;
	private bool $lazyLoaded = false;
	/** @var array<array-key, array{entries: array<array-key, ConfigLexiconEntry>, strictness: ConfigLexiconStrictness}> ['app_id' => ['strictness' => ConfigLexiconStrictness, 'entries' => ['config_key' => ConfigLexiconEntry[]]] */
	private array $configLexiconDetails = [];

	/** @var ?array<string, string> */
	private ?array $appVersionsCache = null;

	public function __construct(
		protected IDBConnection $connection,
		protected LoggerInterface $logger,
		protected ICrypto $crypto,
	) {
	}

	/**
	 * @inheritDoc
	 *
	 * @return list<string> list of app ids
	 * @since 7.0.0
	 */
	public function getApps(): array {
		$this->loadConfigAll();
		$apps = array_merge(array_keys($this->fastCache), array_keys($this->lazyCache));
		sort($apps);

		return array_values(array_unique($apps));
	}

	/**
	 * @inheritDoc
	 *
	 * @param string $app id of the app
	 *
	 * @return list<string> list of stored config keys
	 * @since 29.0.0
	 */
	public function getKeys(string $app): array {
		$this->assertParams($app);
		$this->loadConfigAll($app);
		$keys = array_merge(array_keys($this->fastCache[$app] ?? []), array_keys($this->lazyCache[$app] ?? []));
		sort($keys);

		return array_values(array_unique($keys));
	}

	/**
	 * @inheritDoc
	 *
	 * @param string $app id of the app
	 * @param string $key config key
	 * @param bool|null $lazy TRUE to search within lazy loaded config, NULL to search within all config
	 *
	 * @return bool TRUE if key exists
	 * @since 7.0.0
	 * @since 29.0.0 Added the $lazy argument
	 */
	public function hasKey(string $app, string $key, ?bool $lazy = false): bool {
		$this->assertParams($app, $key);
		$this->loadConfig($app, $lazy);

		if ($lazy === null) {
			$appCache = $this->getAllValues($app);
			return isset($appCache[$key]);
		}

		if ($lazy) {
			return isset($this->lazyCache[$app][$key]);
		}

		return isset($this->fastCache[$app][$key]);
	}

	/**
	 * @param string $app id of the app
	 * @param string $key config key
	 * @param bool|null $lazy TRUE to search within lazy loaded config, NULL to search within all config
	 *
	 * @return bool
	 * @throws AppConfigUnknownKeyException if config key is not known
	 * @since 29.0.0
	 */
	public function isSensitive(string $app, string $key, ?bool $lazy = false): bool {
		$this->assertParams($app, $key);
		$this->loadConfig(null, $lazy);

		if (!isset($this->valueTypes[$app][$key])) {
			throw new AppConfigUnknownKeyException('unknown config key');
		}

		return $this->isTyped(self::VALUE_SENSITIVE, $this->valueTypes[$app][$key]);
	}

	/**
	 * @inheritDoc
	 *
	 * @param string $app if of the app
	 * @param string $key config key
	 *
	 * @return bool TRUE if config is lazy loaded
	 * @throws AppConfigUnknownKeyException if config key is not known
	 * @see IAppConfig for details about lazy loading
	 * @since 29.0.0
	 */
	public function isLazy(string $app, string $key): bool {
		// there is a huge probability the non-lazy config are already loaded
		if ($this->hasKey($app, $key, false)) {
			return false;
		}

		// key not found, we search in the lazy config
		if ($this->hasKey($app, $key, true)) {
			return true;
		}

		throw new AppConfigUnknownKeyException('unknown config key');
	}


	/**
	 * @inheritDoc
	 *
	 * @param string $app id of the app
	 * @param string $prefix config keys prefix to search
	 * @param bool $filtered TRUE to hide sensitive config values. Value are replaced by {@see IConfig::SENSITIVE_VALUE}
	 *
	 * @return array<string, string|int|float|bool|array> [configKey => configValue]
	 * @since 29.0.0
	 */
	public function getAllValues(string $app, string $prefix = '', bool $filtered = false): array {
		$this->assertParams($app, $prefix);
		// if we want to filter values, we need to get sensitivity
		$this->loadConfigAll($app);
		// array_merge() will remove numeric keys (here config keys), so addition arrays instead
		$values = $this->formatAppValues($app, ($this->fastCache[$app] ?? []) + ($this->lazyCache[$app] ?? []));
		$values = array_filter(
			$values,
			function (string $key) use ($prefix): bool {
				return str_starts_with($key, $prefix); // filter values based on $prefix
			}, ARRAY_FILTER_USE_KEY
		);

		if (!$filtered) {
			return $values;
		}

		/**
		 * Using the old (deprecated) list of sensitive values.
		 */
		foreach ($this->getSensitiveKeys($app) as $sensitiveKeyExp) {
			$sensitiveKeys = preg_grep($sensitiveKeyExp, array_keys($values));
			foreach ($sensitiveKeys as $sensitiveKey) {
				$this->valueTypes[$app][$sensitiveKey] = ($this->valueTypes[$app][$sensitiveKey] ?? 0) | self::VALUE_SENSITIVE;
			}
		}

		$result = [];
		foreach ($values as $key => $value) {
			$result[$key] = $this->isTyped(self::VALUE_SENSITIVE, $this->valueTypes[$app][$key] ?? 0) ? IConfig::SENSITIVE_VALUE : $value;
		}

		return $result;
	}

	/**
	 * @inheritDoc
	 *
	 * @param string $key config key
	 * @param bool $lazy search within lazy loaded config
	 * @param int|null $typedAs enforce type for the returned values ({@see self::VALUE_STRING} and others)
	 *
	 * @return array<string, string|int|float|bool|array> [appId => configValue]
	 * @since 29.0.0
	 */
	public function searchValues(string $key, bool $lazy = false, ?int $typedAs = null): array {
		$this->assertParams('', $key, true);
		$this->loadConfig(null, $lazy);

		/** @var array<array-key, array<array-key, mixed>> $cache */
		if ($lazy) {
			$cache = $this->lazyCache;
		} else {
			$cache = $this->fastCache;
		}

		$values = [];
		foreach (array_keys($cache) as $app) {
			if (isset($cache[$app][$key])) {
				$values[$app] = $this->convertTypedValue($cache[$app][$key], $typedAs ?? $this->getValueType((string)$app, $key, $lazy));
			}
		}

		return $values;
	}


	/**
	 * Get the config value as string.
	 * If the value does not exist the given default will be returned.
	 *
	 * Set lazy to `null` to ignore it and get the value from either source.
	 *
	 * **WARNING:** Method is internal and **SHOULD** not be used, as it is better to get the value with a type.
	 *
	 * @param string $app id of the app
	 * @param string $key config key
	 * @param string $default config value
	 * @param null|bool $lazy get config as lazy loaded or not. can be NULL
	 *
	 * @return string the value or $default
	 * @internal
	 * @since 29.0.0
	 * @see IAppConfig for explanation about lazy loading
	 * @see getValueString()
	 * @see getValueInt()
	 * @see getValueFloat()
	 * @see getValueBool()
	 * @see getValueArray()
	 */
	public function getValueMixed(
		string $app,
		string $key,
		string $default = '',
		?bool $lazy = false,
	): string {
		try {
			$lazy = ($lazy === null) ? $this->isLazy($app, $key) : $lazy;
		} catch (AppConfigUnknownKeyException $e) {
			return $default;
		}

		return $this->getTypedValue(
			$app,
			$key,
			$default,
			$lazy,
			self::VALUE_MIXED
		);
	}

	/**
	 * @inheritDoc
	 *
	 * @param string $app id of the app
	 * @param string $key config key
	 * @param string $default default value
	 * @param bool $lazy search within lazy loaded config
	 *
	 * @return string stored config value or $default if not set in database
	 * @throws InvalidArgumentException if one of the argument format is invalid
	 * @throws AppConfigTypeConflictException in case of conflict with the value type set in database
	 * @since 29.0.0
	 * @see IAppConfig for explanation about lazy loading
	 */
	public function getValueString(
		string $app,
		string $key,
		string $default = '',
		bool $lazy = false,
	): string {
		return $this->getTypedValue($app, $key, $default, $lazy, self::VALUE_STRING);
	}

	/**
	 * @inheritDoc
	 *
	 * @param string $app id of the app
	 * @param string $key config key
	 * @param int $default default value
	 * @param bool $lazy search within lazy loaded config
	 *
	 * @return int stored config value or $default if not set in database
	 * @throws InvalidArgumentException if one of the argument format is invalid
	 * @throws AppConfigTypeConflictException in case of conflict with the value type set in database
	 * @since 29.0.0
	 * @see IAppConfig for explanation about lazy loading
	 */
	public function getValueInt(
		string $app,
		string $key,
		int $default = 0,
		bool $lazy = false,
	): int {
		return (int)$this->getTypedValue($app, $key, (string)$default, $lazy, self::VALUE_INT);
	}

	/**
	 * @inheritDoc
	 *
	 * @param string $app id of the app
	 * @param string $key config key
	 * @param float $default default value
	 * @param bool $lazy search within lazy loaded config
	 *
	 * @return float stored config value or $default if not set in database
	 * @throws InvalidArgumentException if one of the argument format is invalid
	 * @throws AppConfigTypeConflictException in case of conflict with the value type set in database
	 * @since 29.0.0
	 * @see IAppConfig for explanation about lazy loading
	 */
	public function getValueFloat(string $app, string $key, float $default = 0, bool $lazy = false): float {
		return (float)$this->getTypedValue($app, $key, (string)$default, $lazy, self::VALUE_FLOAT);
	}

	/**
	 * @inheritDoc
	 *
	 * @param string $app id of the app
	 * @param string $key config key
	 * @param bool $default default value
	 * @param bool $lazy search within lazy loaded config
	 *
	 * @return bool stored config value or $default if not set in database
	 * @throws InvalidArgumentException if one of the argument format is invalid
	 * @throws AppConfigTypeConflictException in case of conflict with the value type set in database
	 * @since 29.0.0
	 * @see IAppConfig for explanation about lazy loading
	 */
	public function getValueBool(string $app, string $key, bool $default = false, bool $lazy = false): bool {
		$b = strtolower($this->getTypedValue($app, $key, $default ? 'true' : 'false', $lazy, self::VALUE_BOOL));
		return in_array($b, ['1', 'true', 'yes', 'on']);
	}

	/**
	 * @inheritDoc
	 *
	 * @param string $app id of the app
	 * @param string $key config key
	 * @param array $default default value
	 * @param bool $lazy search within lazy loaded config
	 *
	 * @return array stored config value or $default if not set in database
	 * @throws InvalidArgumentException if one of the argument format is invalid
	 * @throws AppConfigTypeConflictException in case of conflict with the value type set in database
	 * @since 29.0.0
	 * @see IAppConfig for explanation about lazy loading
	 */
	public function getValueArray(
		string $app,
		string $key,
		array $default = [],
		bool $lazy = false,
	): array {
		try {
			$defaultJson = json_encode($default, JSON_THROW_ON_ERROR);
			$value = json_decode($this->getTypedValue($app, $key, $defaultJson, $lazy, self::VALUE_ARRAY), true, flags: JSON_THROW_ON_ERROR);

			return is_array($value) ? $value : [];
		} catch (JsonException) {
			return [];
		}
	}

	/**
	 * @param string $app id of the app
	 * @param string $key config key
	 * @param string $default default value
	 * @param bool $lazy search within lazy loaded config
	 * @param int $type value type {@see VALUE_STRING} {@see VALUE_INT}{@see VALUE_FLOAT} {@see VALUE_BOOL} {@see VALUE_ARRAY}
	 *
	 * @return string
	 * @throws AppConfigTypeConflictException if type from database is not VALUE_MIXED and different from the requested one
	 * @throws InvalidArgumentException
	 */
	private function getTypedValue(
		string $app,
		string $key,
		string $default,
		bool $lazy,
		int $type,
	): string {
		$this->assertParams($app, $key, valueType: $type);
		if (!$this->matchAndApplyLexiconDefinition($app, $key, $lazy, $type, $default)) {
			return $default; // returns default if strictness of lexicon is set to WARNING (block and report)
		}
		$this->loadConfig($app, $lazy);

		/**
		 * We ignore check if mixed type is requested.
		 * If type of stored value is set as mixed, we don't filter.
		 * If type of stored value is defined, we compare with the one requested.
		 */
		$knownType = $this->valueTypes[$app][$key] ?? 0;
		if (!$this->isTyped(self::VALUE_MIXED, $type)
			&& $knownType > 0
			&& !$this->isTyped(self::VALUE_MIXED, $knownType)
			&& !$this->isTyped($type, $knownType)) {
			$this->logger->warning('conflict with value type from database', ['app' => $app, 'key' => $key, 'type' => $type, 'knownType' => $knownType]);
			throw new AppConfigTypeConflictException('conflict with value type from database');
		}

		/**
		 * - the pair $app/$key cannot exist in both array,
		 * - we should still return an existing non-lazy value even if current method
		 *   is called with $lazy is true
		 *
		 * This way, lazyCache will be empty until the load for lazy config value is requested.
		 */
		if (isset($this->lazyCache[$app][$key])) {
			$value = $this->lazyCache[$app][$key];
		} elseif (isset($this->fastCache[$app][$key])) {
			$value = $this->fastCache[$app][$key];
		} else {
			return $default;
		}

		$sensitive = $this->isTyped(self::VALUE_SENSITIVE, $knownType);
		if ($sensitive && str_starts_with($value, self::ENCRYPTION_PREFIX)) {
			// Only decrypt values that are stored encrypted
			$value = $this->crypto->decrypt(substr($value, self::ENCRYPTION_PREFIX_LENGTH));
		}

		return $value;
	}

	/**
	 * @inheritDoc
	 *
	 * @param string $app id of the app
	 * @param string $key config key
	 *
	 * @return int type of the value
	 * @throws AppConfigUnknownKeyException if config key is not known
	 * @since 29.0.0
	 * @see VALUE_STRING
	 * @see VALUE_INT
	 * @see VALUE_FLOAT
	 * @see VALUE_BOOL
	 * @see VALUE_ARRAY
	 */
	public function getValueType(string $app, string $key, ?bool $lazy = null): int {
		$type = self::VALUE_MIXED;
		$ignorable = $lazy ?? false;
		$this->matchAndApplyLexiconDefinition($app, $key, $ignorable, $type);
		if ($type !== self::VALUE_MIXED) {
			// a modified $type means config key is set in Lexicon
			return $type;
		}

		$this->assertParams($app, $key);
		$this->loadConfig($app, $lazy);

		if (!isset($this->valueTypes[$app][$key])) {
			throw new AppConfigUnknownKeyException('unknown config key');
		}

		$type = $this->valueTypes[$app][$key];
		$type &= ~self::VALUE_SENSITIVE;
		return $type;
	}


	/**
	 * Store a config key and its value in database as VALUE_MIXED
	 *
	 * **WARNING:** Method is internal and **MUST** not be used as it is best to set a real value type
	 *
	 * @param string $app id of the app
	 * @param string $key config key
	 * @param string $value config value
	 * @param bool $lazy set config as lazy loaded
	 * @param bool $sensitive if TRUE value will be hidden when listing config values.
	 *
	 * @return bool TRUE if value was different, therefor updated in database
	 * @throws AppConfigTypeConflictException if type from database is not VALUE_MIXED
	 * @internal
	 * @since 29.0.0
	 * @see IAppConfig for explanation about lazy loading
	 * @see setValueString()
	 * @see setValueInt()
	 * @see setValueFloat()
	 * @see setValueBool()
	 * @see setValueArray()
	 */
	public function setValueMixed(
		string $app,
		string $key,
		string $value,
		bool $lazy = false,
		bool $sensitive = false,
	): bool {
		return $this->setTypedValue(
			$app,
			$key,
			$value,
			$lazy,
			self::VALUE_MIXED | ($sensitive ? self::VALUE_SENSITIVE : 0)
		);
	}


	/**
	 * @inheritDoc
	 *
	 * @param string $app id of the app
	 * @param string $key config key
	 * @param string $value config value
	 * @param bool $lazy set config as lazy loaded
	 * @param bool $sensitive if TRUE value will be hidden when listing config values.
	 *
	 * @return bool TRUE if value was different, therefor updated in database
	 * @throws AppConfigTypeConflictException if type from database is not VALUE_MIXED and different from the requested one
	 * @since 29.0.0
	 * @see IAppConfig for explanation about lazy loading
	 */
	public function setValueString(
		string $app,
		string $key,
		string $value,
		bool $lazy = false,
		bool $sensitive = false,
	): bool {
		return $this->setTypedValue(
			$app,
			$key,
			$value,
			$lazy,
			self::VALUE_STRING | ($sensitive ? self::VALUE_SENSITIVE : 0)
		);
	}

	/**
	 * @inheritDoc
	 *
	 * @param string $app id of the app
	 * @param string $key config key
	 * @param int $value config value
	 * @param bool $lazy set config as lazy loaded
	 * @param bool $sensitive if TRUE value will be hidden when listing config values.
	 *
	 * @return bool TRUE if value was different, therefor updated in database
	 * @throws AppConfigTypeConflictException if type from database is not VALUE_MIXED and different from the requested one
	 * @since 29.0.0
	 * @see IAppConfig for explanation about lazy loading
	 */
	public function setValueInt(
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
			$app,
			$key,
			(string)$value,
			$lazy,
			self::VALUE_INT | ($sensitive ? self::VALUE_SENSITIVE : 0)
		);
	}

	/**
	 * @inheritDoc
	 *
	 * @param string $app id of the app
	 * @param string $key config key
	 * @param float $value config value
	 * @param bool $lazy set config as lazy loaded
	 * @param bool $sensitive if TRUE value will be hidden when listing config values.
	 *
	 * @return bool TRUE if value was different, therefor updated in database
	 * @throws AppConfigTypeConflictException if type from database is not VALUE_MIXED and different from the requested one
	 * @since 29.0.0
	 * @see IAppConfig for explanation about lazy loading
	 */
	public function setValueFloat(
		string $app,
		string $key,
		float $value,
		bool $lazy = false,
		bool $sensitive = false,
	): bool {
		return $this->setTypedValue(
			$app,
			$key,
			(string)$value,
			$lazy,
			self::VALUE_FLOAT | ($sensitive ? self::VALUE_SENSITIVE : 0)
		);
	}

	/**
	 * @inheritDoc
	 *
	 * @param string $app id of the app
	 * @param string $key config key
	 * @param bool $value config value
	 * @param bool $lazy set config as lazy loaded
	 *
	 * @return bool TRUE if value was different, therefor updated in database
	 * @throws AppConfigTypeConflictException if type from database is not VALUE_MIXED and different from the requested one
	 * @since 29.0.0
	 * @see IAppConfig for explanation about lazy loading
	 */
	public function setValueBool(
		string $app,
		string $key,
		bool $value,
		bool $lazy = false,
	): bool {
		return $this->setTypedValue(
			$app,
			$key,
			($value) ? '1' : '0',
			$lazy,
			self::VALUE_BOOL
		);
	}

	/**
	 * @inheritDoc
	 *
	 * @param string $app id of the app
	 * @param string $key config key
	 * @param array $value config value
	 * @param bool $lazy set config as lazy loaded
	 * @param bool $sensitive if TRUE value will be hidden when listing config values.
	 *
	 * @return bool TRUE if value was different, therefor updated in database
	 * @throws AppConfigTypeConflictException if type from database is not VALUE_MIXED and different from the requested one
	 * @throws JsonException
	 * @since 29.0.0
	 * @see IAppConfig for explanation about lazy loading
	 */
	public function setValueArray(
		string $app,
		string $key,
		array $value,
		bool $lazy = false,
		bool $sensitive = false,
	): bool {
		try {
			return $this->setTypedValue(
				$app,
				$key,
				json_encode($value, JSON_THROW_ON_ERROR),
				$lazy,
				self::VALUE_ARRAY | ($sensitive ? self::VALUE_SENSITIVE : 0)
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
	 * @param string $app id of the app
	 * @param string $key config key
	 * @param string $value config value
	 * @param bool $lazy config set as lazy loaded
	 * @param int $type value type {@see VALUE_STRING} {@see VALUE_INT} {@see VALUE_FLOAT} {@see VALUE_BOOL} {@see VALUE_ARRAY}
	 *
	 * @return bool TRUE if value was updated in database
	 * @throws AppConfigTypeConflictException if type from database is not VALUE_MIXED and different from the requested one
	 * @see IAppConfig for explanation about lazy loading
	 */
	private function setTypedValue(
		string $app,
		string $key,
		string $value,
		bool $lazy,
		int $type,
	): bool {
		$this->assertParams($app, $key);
		if (!$this->matchAndApplyLexiconDefinition($app, $key, $lazy, $type)) {
			return false; // returns false as database is not updated
		}
		$this->loadConfig(null, $lazy);

		$sensitive = $this->isTyped(self::VALUE_SENSITIVE, $type);
		$inserted = $refreshCache = false;

		$origValue = $value;
		if ($sensitive || ($this->hasKey($app, $key, $lazy) && $this->isSensitive($app, $key, $lazy))) {
			$value = self::ENCRYPTION_PREFIX . $this->crypto->encrypt($value);
		}

		if ($this->hasKey($app, $key, $lazy)) {
			/**
			 * no update if key is already known with set lazy status and value is
			 * not different, unless sensitivity is switched from false to true.
			 */
			if ($origValue === $this->getTypedValue($app, $key, $value, $lazy, $type)
				&& (!$sensitive || $this->isSensitive($app, $key, $lazy))) {
				return false;
			}
		} else {
			/**
			 * if key is not known yet, we try to insert.
			 * It might fail if the key exists with a different lazy flag.
			 */
			try {
				$insert = $this->connection->getQueryBuilder();
				$insert->insert('appconfig')
					->setValue('appid', $insert->createNamedParameter($app))
					->setValue('lazy', $insert->createNamedParameter(($lazy) ? 1 : 0, IQueryBuilder::PARAM_INT))
					->setValue('type', $insert->createNamedParameter($type, IQueryBuilder::PARAM_INT))
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
			$currType = $this->valueTypes[$app][$key] ?? 0;
			if ($currType === 0) { // this might happen when switching lazy loading status
				$this->loadConfigAll();
				$currType = $this->valueTypes[$app][$key] ?? 0;
			}

			/**
			 * This should only happen during the upgrade process from 28 to 29.
			 * We only log a warning and set it to VALUE_MIXED.
			 */
			if ($currType === 0) {
				$this->logger->warning('Value type is set to zero (0) in database. This is fine only during the upgrade process from 28 to 29.', ['app' => $app, 'key' => $key]);
				$currType = self::VALUE_MIXED;
			}

			/**
			 * we only accept a different type from the one stored in database
			 * if the one stored in database is not-defined (VALUE_MIXED)
			 */
			if (!$this->isTyped(self::VALUE_MIXED, $currType) &&
				($type | self::VALUE_SENSITIVE) !== ($currType | self::VALUE_SENSITIVE)) {
				try {
					$currType = $this->convertTypeToString($currType);
					$type = $this->convertTypeToString($type);
				} catch (AppConfigIncorrectTypeException) {
					// can be ignored, this was just needed for a better exception message.
				}
				throw new AppConfigTypeConflictException('conflict between new type (' . $type . ') and old type (' . $currType . ')');
			}

			// we fix $type if the stored value, or the new value as it might be changed, is set as sensitive
			if ($sensitive || $this->isTyped(self::VALUE_SENSITIVE, $currType)) {
				$type |= self::VALUE_SENSITIVE;
			}

			if ($lazy !== $this->isLazy($app, $key)) {
				$refreshCache = true;
			}

			$update = $this->connection->getQueryBuilder();
			$update->update('appconfig')
				->set('configvalue', $update->createNamedParameter($value))
				->set('lazy', $update->createNamedParameter(($lazy) ? 1 : 0, IQueryBuilder::PARAM_INT))
				->set('type', $update->createNamedParameter($type, IQueryBuilder::PARAM_INT))
				->where($update->expr()->eq('appid', $update->createNamedParameter($app)))
				->andWhere($update->expr()->eq('configkey', $update->createNamedParameter($key)));

			$update->executeStatement();
		}

		if ($refreshCache) {
			$this->clearCache();
			return true;
		}

		// update local cache
		if ($lazy) {
			$this->lazyCache[$app][$key] = $value;
		} else {
			$this->fastCache[$app][$key] = $value;
		}
		$this->valueTypes[$app][$key] = $type;

		return true;
	}

	/**
	 * Change the type of config value.
	 *
	 * **WARNING:** Method is internal and **MUST** not be used as it may break things.
	 *
	 * @param string $app id of the app
	 * @param string $key config key
	 * @param int $type value type {@see VALUE_STRING} {@see VALUE_INT} {@see VALUE_FLOAT} {@see VALUE_BOOL} {@see VALUE_ARRAY}
	 *
	 * @return bool TRUE if database update were necessary
	 * @throws AppConfigUnknownKeyException if $key is now known in database
	 * @throws AppConfigIncorrectTypeException if $type is not valid
	 * @internal
	 * @since 29.0.0
	 */
	public function updateType(string $app, string $key, int $type = self::VALUE_MIXED): bool {
		$this->assertParams($app, $key);
		$this->loadConfigAll();
		$lazy = $this->isLazy($app, $key);

		// type can only be one type
		if (!in_array($type, [self::VALUE_MIXED, self::VALUE_STRING, self::VALUE_INT, self::VALUE_FLOAT, self::VALUE_BOOL, self::VALUE_ARRAY])) {
			throw new AppConfigIncorrectTypeException('Unknown value type');
		}

		$currType = $this->valueTypes[$app][$key];
		if (($type | self::VALUE_SENSITIVE) === ($currType | self::VALUE_SENSITIVE)) {
			return false;
		}

		// we complete with sensitive flag if the stored value is set as sensitive
		if ($this->isTyped(self::VALUE_SENSITIVE, $currType)) {
			$type = $type | self::VALUE_SENSITIVE;
		}

		$update = $this->connection->getQueryBuilder();
		$update->update('appconfig')
			->set('type', $update->createNamedParameter($type, IQueryBuilder::PARAM_INT))
			->where($update->expr()->eq('appid', $update->createNamedParameter($app)))
			->andWhere($update->expr()->eq('configkey', $update->createNamedParameter($key)));
		$update->executeStatement();
		$this->valueTypes[$app][$key] = $type;

		return true;
	}


	/**
	 * @inheritDoc
	 *
	 * @param string $app id of the app
	 * @param string $key config key
	 * @param bool $sensitive TRUE to set as sensitive, FALSE to unset
	 *
	 * @return bool TRUE if entry was found in database and an update was necessary
	 * @since 29.0.0
	 */
	public function updateSensitive(string $app, string $key, bool $sensitive): bool {
		$this->assertParams($app, $key);
		$this->loadConfigAll();

		try {
			if ($sensitive === $this->isSensitive($app, $key, null)) {
				return false;
			}
		} catch (AppConfigUnknownKeyException $e) {
			return false;
		}

		$lazy = $this->isLazy($app, $key);
		if ($lazy) {
			$cache = $this->lazyCache;
		} else {
			$cache = $this->fastCache;
		}

		if (!isset($cache[$app][$key])) {
			throw new AppConfigUnknownKeyException('unknown config key');
		}

		/**
		 * type returned by getValueType() is already cleaned from sensitive flag
		 * we just need to update it based on $sensitive and store it in database
		 */
		$type = $this->getValueType($app, $key);
		$value = $cache[$app][$key];
		if ($sensitive) {
			$type |= self::VALUE_SENSITIVE;
			$value = self::ENCRYPTION_PREFIX . $this->crypto->encrypt($value);
		} else {
			$value = $this->crypto->decrypt(substr($value, self::ENCRYPTION_PREFIX_LENGTH));
		}

		$update = $this->connection->getQueryBuilder();
		$update->update('appconfig')
			->set('type', $update->createNamedParameter($type, IQueryBuilder::PARAM_INT))
			->set('configvalue', $update->createNamedParameter($value))
			->where($update->expr()->eq('appid', $update->createNamedParameter($app)))
			->andWhere($update->expr()->eq('configkey', $update->createNamedParameter($key)));
		$update->executeStatement();

		$this->valueTypes[$app][$key] = $type;

		return true;
	}

	/**
	 * @inheritDoc
	 *
	 * @param string $app id of the app
	 * @param string $key config key
	 * @param bool $lazy TRUE to set as lazy loaded, FALSE to unset
	 *
	 * @return bool TRUE if entry was found in database and an update was necessary
	 * @since 29.0.0
	 */
	public function updateLazy(string $app, string $key, bool $lazy): bool {
		$this->assertParams($app, $key);
		$this->loadConfigAll();

		try {
			if ($lazy === $this->isLazy($app, $key)) {
				return false;
			}
		} catch (AppConfigUnknownKeyException $e) {
			return false;
		}

		$update = $this->connection->getQueryBuilder();
		$update->update('appconfig')
			->set('lazy', $update->createNamedParameter($lazy ? 1 : 0, IQueryBuilder::PARAM_INT))
			->where($update->expr()->eq('appid', $update->createNamedParameter($app)))
			->andWhere($update->expr()->eq('configkey', $update->createNamedParameter($key)));
		$update->executeStatement();

		// At this point, it is a lot safer to clean cache
		$this->clearCache();

		return true;
	}

	/**
	 * @inheritDoc
	 *
	 * @param string $app id of the app
	 * @param string $key config key
	 *
	 * @return array
	 * @throws AppConfigUnknownKeyException if config key is not known in database
	 * @since 29.0.0
	 */
	public function getDetails(string $app, string $key): array {
		$this->assertParams($app, $key);
		$this->loadConfigAll();
		$lazy = $this->isLazy($app, $key);

		if ($lazy) {
			$cache = $this->lazyCache;
		} else {
			$cache = $this->fastCache;
		}

		$type = $this->getValueType($app, $key);
		try {
			$typeString = $this->convertTypeToString($type);
		} catch (AppConfigIncorrectTypeException $e) {
			$this->logger->warning('type stored in database is not correct', ['exception' => $e, 'type' => $type]);
			$typeString = (string)$type;
		}

		if (!isset($cache[$app][$key])) {
			throw new AppConfigUnknownKeyException('unknown config key');
		}

		$value = $cache[$app][$key];
		$sensitive = $this->isSensitive($app, $key, null);
		if ($sensitive && str_starts_with($value, self::ENCRYPTION_PREFIX)) {
			$value = $this->crypto->decrypt(substr($value, self::ENCRYPTION_PREFIX_LENGTH));
		}

		return [
			'app' => $app,
			'key' => $key,
			'value' => $value,
			'type' => $type,
			'lazy' => $lazy,
			'typeString' => $typeString,
			'sensitive' => $sensitive
		];
	}

	/**
	 * @param string $type
	 *
	 * @return int
	 * @throws AppConfigIncorrectTypeException
	 * @since 29.0.0
	 */
	public function convertTypeToInt(string $type): int {
		return match (strtolower($type)) {
			'mixed' => IAppConfig::VALUE_MIXED,
			'string' => IAppConfig::VALUE_STRING,
			'integer' => IAppConfig::VALUE_INT,
			'float' => IAppConfig::VALUE_FLOAT,
			'boolean' => IAppConfig::VALUE_BOOL,
			'array' => IAppConfig::VALUE_ARRAY,
			default => throw new AppConfigIncorrectTypeException('Unknown type ' . $type)
		};
	}

	/**
	 * @param int $type
	 *
	 * @return string
	 * @throws AppConfigIncorrectTypeException
	 * @since 29.0.0
	 */
	public function convertTypeToString(int $type): string {
		$type &= ~self::VALUE_SENSITIVE;

		return match ($type) {
			IAppConfig::VALUE_MIXED => 'mixed',
			IAppConfig::VALUE_STRING => 'string',
			IAppConfig::VALUE_INT => 'integer',
			IAppConfig::VALUE_FLOAT => 'float',
			IAppConfig::VALUE_BOOL => 'boolean',
			IAppConfig::VALUE_ARRAY => 'array',
			default => throw new AppConfigIncorrectTypeException('Unknown numeric type ' . $type)
		};
	}

	/**
	 * @inheritDoc
	 *
	 * @param string $app id of the app
	 * @param string $key config key
	 *
	 * @since 29.0.0
	 */
	public function deleteKey(string $app, string $key): void {
		$this->assertParams($app, $key);
		$qb = $this->connection->getQueryBuilder();
		$qb->delete('appconfig')
			->where($qb->expr()->eq('appid', $qb->createNamedParameter($app)))
			->andWhere($qb->expr()->eq('configkey', $qb->createNamedParameter($key)));
		$qb->executeStatement();

		unset($this->lazyCache[$app][$key]);
		unset($this->fastCache[$app][$key]);
		unset($this->valueTypes[$app][$key]);
	}

	/**
	 * @inheritDoc
	 *
	 * @param string $app id of the app
	 *
	 * @since 29.0.0
	 */
	public function deleteApp(string $app): void {
		$this->assertParams($app);
		$qb = $this->connection->getQueryBuilder();
		$qb->delete('appconfig')
			->where($qb->expr()->eq('appid', $qb->createNamedParameter($app)));
		$qb->executeStatement();

		$this->clearCache();
	}

	/**
	 * @inheritDoc
	 *
	 * @param bool $reload set to TRUE to refill cache instantly after clearing it
	 *
	 * @since 29.0.0
	 */
	public function clearCache(bool $reload = false): void {
		$this->lazyLoaded = $this->fastLoaded = false;
		$this->lazyCache = $this->fastCache = $this->valueTypes = [];

		if (!$reload) {
			return;
		}

		$this->loadConfigAll();
	}


	/**
	 * For debug purpose.
	 * Returns the cached data.
	 *
	 * @return array
	 * @since 29.0.0
	 * @internal
	 */
	public function statusCache(): array {
		return [
			'fastLoaded' => $this->fastLoaded,
			'fastCache' => $this->fastCache,
			'lazyLoaded' => $this->lazyLoaded,
			'lazyCache' => $this->lazyCache,
		];
	}

	/**
	 * @param int $needle bitflag to search
	 * @param int $type known value
	 *
	 * @return bool TRUE if bitflag $needle is set in $type
	 */
	private function isTyped(int $needle, int $type): bool {
		return (($needle & $type) !== 0);
	}

	/**
	 * Confirm the string set for app and key fit the database description
	 *
	 * @param string $app assert $app fit in database
	 * @param string $configKey assert config key fit in database
	 * @param bool $allowEmptyApp $app can be empty string
	 * @param int $valueType assert value type is only one type
	 *
	 * @throws InvalidArgumentException
	 */
	private function assertParams(string $app = '', string $configKey = '', bool $allowEmptyApp = false, int $valueType = -1): void {
		if (!$allowEmptyApp && $app === '') {
			throw new InvalidArgumentException('app cannot be an empty string');
		}
		if (strlen($app) > self::APP_MAX_LENGTH) {
			throw new InvalidArgumentException(
				'Value (' . $app . ') for app is too long (' . self::APP_MAX_LENGTH . ')'
			);
		}
		if (strlen($configKey) > self::KEY_MAX_LENGTH) {
			throw new InvalidArgumentException('Value (' . $configKey . ') for key is too long (' . self::KEY_MAX_LENGTH . ')');
		}
		if ($valueType > -1) {
			$valueType &= ~self::VALUE_SENSITIVE;
			if (!in_array($valueType, [self::VALUE_MIXED, self::VALUE_STRING, self::VALUE_INT, self::VALUE_FLOAT, self::VALUE_BOOL, self::VALUE_ARRAY])) {
				throw new InvalidArgumentException('Unknown value type');
			}
		}
	}

	private function loadConfigAll(?string $app = null): void {
		$this->loadConfig($app, null);
	}

	/**
	 * Load normal config or config set as lazy loaded
	 *
	 * @param bool|null $lazy set to TRUE to load config set as lazy loaded, set to NULL to load all config
	 */
	private function loadConfig(?string $app = null, ?bool $lazy = false): void {
		if ($this->isLoaded($lazy)) {
			return;
		}

		// if lazy is null or true, we debug log
		if (($lazy ?? true) !== false && $app !== null) {
			$exception = new \RuntimeException('The loading of lazy AppConfig values have been triggered by app "' . $app . '"');
			$this->logger->debug($exception->getMessage(), ['exception' => $exception, 'app' => $app]);
		}

		$qb = $this->connection->getQueryBuilder();
		$qb->from('appconfig');

		// we only need value from lazy when loadConfig does not specify it
		$qb->select('appid', 'configkey', 'configvalue', 'type');

		if ($lazy !== null) {
			$qb->where($qb->expr()->eq('lazy', $qb->createNamedParameter($lazy ? 1 : 0, IQueryBuilder::PARAM_INT)));
		} else {
			$qb->addSelect('lazy');
		}

		$result = $qb->executeQuery();
		$rows = $result->fetchAll();
		foreach ($rows as $row) {
			// most of the time, 'lazy' is not in the select because its value is already known
			if (($row['lazy'] ?? ($lazy ?? 0) ? 1 : 0) === 1) {
				$this->lazyCache[$row['appid']][$row['configkey']] = $row['configvalue'] ?? '';
			} else {
				$this->fastCache[$row['appid']][$row['configkey']] = $row['configvalue'] ?? '';
			}
			$this->valueTypes[$row['appid']][$row['configkey']] = (int)($row['type'] ?? 0);
		}
		$result->closeCursor();
		$this->setAsLoaded($lazy);
	}

	/**
	 * if $lazy is:
	 *  - false: will returns true if fast config is loaded
	 *  - true : will returns true if lazy config is loaded
	 *  - null : will returns true if both config are loaded
	 *
	 * @param bool $lazy
	 *
	 * @return bool
	 */
	private function isLoaded(?bool $lazy): bool {
		if ($lazy === null) {
			return $this->lazyLoaded && $this->fastLoaded;
		}

		return $lazy ? $this->lazyLoaded : $this->fastLoaded;
	}

	/**
	 * if $lazy is:
	 * - false: set fast config as loaded
	 * - true : set lazy config as loaded
	 * - null : set both config as loaded
	 *
	 * @param bool $lazy
	 */
	private function setAsLoaded(?bool $lazy): void {
		if ($lazy === null) {
			$this->fastLoaded = true;
			$this->lazyLoaded = true;

			return;
		}

		if ($lazy) {
			$this->lazyLoaded = true;
		} else {
			$this->fastLoaded = true;
		}
	}

	/**
	 * Gets the config value
	 *
	 * @param string $app app
	 * @param string $key key
	 * @param string $default = null, default value if the key does not exist
	 *
	 * @return string the value or $default
	 * @deprecated 29.0.0 use getValue*()
	 *
	 * This function gets a value from the appconfig table. If the key does
	 * not exist the default value will be returned
	 */
	public function getValue($app, $key, $default = null) {
		$this->loadConfig($app);

		return $this->fastCache[$app][$key] ?? $default;
	}

	/**
	 * Sets a value. If the key did not exist before it will be created.
	 *
	 * @param string $app app
	 * @param string $key key
	 * @param string|float|int $value value
	 *
	 * @return bool True if the value was inserted or updated, false if the value was the same
	 * @throws AppConfigTypeConflictException
	 * @throws AppConfigUnknownKeyException
	 * @deprecated 29.0.0
	 */
	public function setValue($app, $key, $value) {
		/**
		 * TODO: would it be overkill, or decently improve performance, to catch
		 * call to this method with $key='enabled' and 'hide' config value related
		 * to $app when the app is disabled (by modifying entry in database: lazy=lazy+2)
		 * or enabled (lazy=lazy-2)
		 *
		 * this solution would remove the loading of config values from disabled app
		 * unless calling the method {@see loadConfigAll()}
		 */
		return $this->setTypedValue($app, $key, (string)$value, false, self::VALUE_MIXED);
	}


	/**
	 * get multiple values, either the app or key can be used as wildcard by setting it to false
	 *
	 * @param string|false $app
	 * @param string|false $key
	 *
	 * @return array|false
	 * @deprecated 29.0.0 use {@see getAllValues()}
	 */
	public function getValues($app, $key) {
		if (($app !== false) === ($key !== false)) {
			return false;
		}

		$key = ($key === false) ? '' : $key;
		if (!$app) {
			return $this->searchValues($key, false, self::VALUE_MIXED);
		} else {
			return $this->getAllValues($app, $key);
		}
	}

	/**
	 * get all values of the app or and filters out sensitive data
	 *
	 * @param string $app
	 *
	 * @return array
	 * @deprecated 29.0.0 use {@see getAllValues()}
	 */
	public function getFilteredValues($app) {
		return $this->getAllValues($app, filtered: true);
	}


	/**
	 * **Warning:** avoid default NULL value for $lazy as this will
	 * load all lazy values from the database
	 *
	 * @param string $app
	 * @param array<string, string> $values ['key' => 'value']
	 * @param bool|null $lazy
	 *
	 * @return array<string, string|int|float|bool|array>
	 */
	private function formatAppValues(string $app, array $values, ?bool $lazy = null): array {
		foreach ($values as $key => $value) {
			try {
				$type = $this->getValueType($app, $key, $lazy);
			} catch (AppConfigUnknownKeyException $e) {
				continue;
			}

			$values[$key] = $this->convertTypedValue($value, $type);
		}

		return $values;
	}

	/**
	 * convert string value to the expected type
	 *
	 * @param string $value
	 * @param int $type
	 *
	 * @return string|int|float|bool|array
	 */
	private function convertTypedValue(string $value, int $type): string|int|float|bool|array {
		switch ($type) {
			case self::VALUE_INT:
				return (int)$value;
			case self::VALUE_FLOAT:
				return (float)$value;
			case self::VALUE_BOOL:
				return in_array(strtolower($value), ['1', 'true', 'yes', 'on']);
			case self::VALUE_ARRAY:
				try {
					return json_decode($value, true, flags: JSON_THROW_ON_ERROR);
				} catch (JsonException $e) {
					// ignoreable
				}
				break;
		}
		return $value;
	}

	/**
	 * @param string $app
	 *
	 * @return string[]
	 * @deprecated 29.0.0 data sensitivity should be set when calling setValue*()
	 */
	private function getSensitiveKeys(string $app): array {
		$sensitiveValues = [
			'circles' => [
				'/^key_pairs$/',
				'/^local_gskey$/',
			],
			'call_summary_bot' => [
				'/^secret_(.*)$/',
			],
			'external' => [
				'/^sites$/',
				'/^jwt_token_privkey_(.*)$/',
			],
			'globalsiteselector' => [
				'/^gss\.jwt\.key$/',
			],
			'gpgmailer' => [
				'/^GpgServerKey$/',
			],
			'integration_discourse' => [
				'/^private_key$/',
				'/^public_key$/',
			],
			'integration_dropbox' => [
				'/^client_id$/',
				'/^client_secret$/',
			],
			'integration_github' => [
				'/^client_id$/',
				'/^client_secret$/',
			],
			'integration_gitlab' => [
				'/^client_id$/',
				'/^client_secret$/',
				'/^oauth_instance_url$/',
			],
			'integration_google' => [
				'/^client_id$/',
				'/^client_secret$/',
			],
			'integration_jira' => [
				'/^client_id$/',
				'/^client_secret$/',
				'/^forced_instance_url$/',
			],
			'integration_onedrive' => [
				'/^client_id$/',
				'/^client_secret$/',
			],
			'integration_openproject' => [
				'/^client_id$/',
				'/^client_secret$/',
				'/^oauth_instance_url$/',
			],
			'integration_reddit' => [
				'/^client_id$/',
				'/^client_secret$/',
			],
			'integration_suitecrm' => [
				'/^client_id$/',
				'/^client_secret$/',
				'/^oauth_instance_url$/',
			],
			'integration_twitter' => [
				'/^consumer_key$/',
				'/^consumer_secret$/',
				'/^followed_user$/',
			],
			'integration_zammad' => [
				'/^client_id$/',
				'/^client_secret$/',
				'/^oauth_instance_url$/',
			],
			'maps' => [
				'/^mapboxAPIKEY$/',
			],
			'notify_push' => [
				'/^cookie$/',
			],
			'onlyoffice' => [
				'/^jwt_secret$/',
			],
			'passwords' => [
				'/^SSEv1ServerKey$/',
			],
			'serverinfo' => [
				'/^token$/',
			],
			'spreed' => [
				'/^bridge_bot_password$/',
				'/^hosted-signaling-server-(.*)$/',
				'/^recording_servers$/',
				'/^signaling_servers$/',
				'/^signaling_ticket_secret$/',
				'/^signaling_token_privkey_(.*)$/',
				'/^signaling_token_pubkey_(.*)$/',
				'/^sip_bridge_dialin_info$/',
				'/^sip_bridge_shared_secret$/',
				'/^stun_servers$/',
				'/^turn_servers$/',
				'/^turn_server_secret$/',
			],
			'support' => [
				'/^last_response$/',
				'/^potential_subscription_key$/',
				'/^subscription_key$/',
			],
			'theming' => [
				'/^imprintUrl$/',
				'/^privacyUrl$/',
				'/^slogan$/',
				'/^url$/',
			],
			'twofactor_gateway' => [
				'/^.*token$/',
			],
			'user_ldap' => [
				'/^(s..)?ldap_agent_password$/',
			],
			'user_saml' => [
				'/^idp-x509cert$/',
			],
			'whiteboard' => [
				'/^jwt_secret_key$/',
			],
		];

		return $sensitiveValues[$app] ?? [];
	}

	/**
	 * Clear all the cached app config values
	 * New cache will be generated next time a config value is retrieved
	 *
	 * @deprecated 29.0.0 use {@see clearCache()}
	 */
	public function clearCachedConfig(): void {
		$this->clearCache();
	}

	/**
	 * match and apply current use of config values with defined lexicon
	 *
	 * @throws AppConfigUnknownKeyException
	 * @throws AppConfigTypeConflictException
	 * @return bool TRUE if everything is fine compared to lexicon or lexicon does not exist
	 */
	private function matchAndApplyLexiconDefinition(
		string $app,
		string $key,
		bool &$lazy,
		int &$type,
		string &$default = '',
	): bool {
		if (in_array($key,
			[
				'enabled',
				'installed_version',
				'types',
			])) {
			return true; // we don't break stuff for this list of config keys.
		}
		$configDetails = $this->getConfigDetailsFromLexicon($app);
		if (!array_key_exists($key, $configDetails['entries'])) {
			return $this->applyLexiconStrictness(
				$configDetails['strictness'],
				'The app config key ' . $app . '/' . $key . ' is not defined in the config lexicon'
			);
		}

		/** @var ConfigLexiconEntry $configValue */
		$configValue = $configDetails['entries'][$key];
		$type &= ~self::VALUE_SENSITIVE;

		$appConfigValueType = $configValue->getValueType()->toAppConfigFlag();
		if ($type === self::VALUE_MIXED) {
			$type = $appConfigValueType; // we overwrite if value was requested as mixed
		} elseif ($appConfigValueType !== $type) {
			throw new AppConfigTypeConflictException('The app config key ' . $app . '/' . $key . ' is typed incorrectly in relation to the config lexicon');
		}

		$lazy = $configValue->isLazy();
		$default = $configValue->getDefault() ?? $default; // default from Lexicon got priority
		if ($configValue->isFlagged(self::FLAG_SENSITIVE)) {
			$type |= self::VALUE_SENSITIVE;
		}
		if ($configValue->isDeprecated()) {
			$this->logger->notice('App config key ' . $app . '/' . $key . ' is set as deprecated.');
		}

		return true;
	}

	/**
	 * manage ConfigLexicon behavior based on strictness set in IConfigLexicon
	 *
	 * @param ConfigLexiconStrictness|null $strictness
	 * @param string $line
	 *
	 * @return bool TRUE if conflict can be fully ignored, FALSE if action should be not performed
	 * @throws AppConfigUnknownKeyException if strictness implies exception
	 * @see IConfigLexicon::getStrictness()
	 */
	private function applyLexiconStrictness(
		?ConfigLexiconStrictness $strictness,
		string $line = '',
	): bool {
		if ($strictness === null) {
			return true;
		}

		switch ($strictness) {
			case ConfigLexiconStrictness::IGNORE:
				return true;
			case ConfigLexiconStrictness::NOTICE:
				$this->logger->notice($line);
				return true;
			case ConfigLexiconStrictness::WARNING:
				$this->logger->warning($line);
				return false;
		}

		throw new AppConfigUnknownKeyException($line);
	}

	/**
	 * extract details from registered $appId's config lexicon
	 *
	 * @param string $appId
	 *
	 * @return array{entries: array<array-key, ConfigLexiconEntry>, strictness: ConfigLexiconStrictness}
	 */
	private function getConfigDetailsFromLexicon(string $appId): array {
		if (!array_key_exists($appId, $this->configLexiconDetails)) {
			$entries = [];
			$bootstrapCoordinator = \OCP\Server::get(Coordinator::class);
			$configLexicon = $bootstrapCoordinator->getRegistrationContext()?->getConfigLexicon($appId);
			foreach ($configLexicon?->getAppConfigs() ?? [] as $configEntry) {
				$entries[$configEntry->getKey()] = $configEntry;
			}

			$this->configLexiconDetails[$appId] = [
				'entries' => $entries,
				'strictness' => $configLexicon?->getStrictness() ?? ConfigLexiconStrictness::IGNORE
			];
		}

		return $this->configLexiconDetails[$appId];
	}

	/**
	 * Returns the installed versions of all apps
	 *
	 * @return array<string, string>
	 */
	public function getAppInstalledVersions(bool $onlyEnabled = false): array {
		if ($this->appVersionsCache === null) {
			/** @var array<string, string> */
			$this->appVersionsCache = $this->searchValues('installed_version', false, IAppConfig::VALUE_STRING);
		}
		if ($onlyEnabled) {
			return array_filter(
				$this->appVersionsCache,
				fn (string $app): bool => $this->getValueString($app, 'enabled', 'no') !== 'no',
				ARRAY_FILTER_USE_KEY
			);
		}
		return $this->appVersionsCache;
	}
}
