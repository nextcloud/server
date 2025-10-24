<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Config;

use JsonException;
use OC\AppConfig;
use OCP\App\IAppManager;
use OCP\Config\Exceptions\TypeConflictException;
use OCP\Config\IUserConfig;
use OCP\Config\Lexicon\Entry;
use OCP\Config\ValueType;
use OCP\IAppConfig;
use OCP\Server;
use Psr\Log\LoggerInterface;

/**
 * tools to maintains configurations
 *
 * @since 32.0.0
 */
class ConfigManager {
	/** @var AppConfig|null $appConfig */
	private ?IAppConfig $appConfig = null;
	/** @var UserConfig|null $userConfig */
	private ?IUserConfig $userConfig = null;

	public function __construct(
		private readonly LoggerInterface $logger,
	) {
	}

	public function clearConfigCaches(): void {
		$this->loadConfigServices();
		$this->appConfig->clearCache();
		$this->userConfig->clearCacheAll();
	}


	/**
	 * Use the rename values from the list of ConfigLexiconEntry defined in each app ConfigLexicon
	 * to migrate config value to a new config key.
	 * Migration will only occur if new config key has no value in database.
	 * The previous value from the key set in rename will be deleted from the database when migration
	 * is over.
	 *
	 * This method should be mainly called during a new upgrade or when a new app is enabled.
	 *
	 * @param string|null $appId when set to NULL the method will be executed for all enabled apps of the instance
	 *
	 * @internal
	 * @since 32.0.0
	 * @see Entry
	 */
	public function migrateConfigLexiconKeys(?string $appId = null): void {
		if ($appId === null) {
			$this->migrateConfigLexiconKeys('core');
			$appManager = Server::get(IAppManager::class);
			foreach ($appManager->getEnabledApps() as $app) {
				$this->migrateConfigLexiconKeys($app);
			}

			return;
		}

		$this->loadConfigServices();

		// it is required to ignore aliases when moving config values
		$this->appConfig->ignoreLexiconAliases(true);
		$this->userConfig->ignoreLexiconAliases(true);

		$this->migrateAppConfigKeys($appId);
		$this->migrateUserConfigKeys($appId);

		// switch back to normal behavior
		$this->appConfig->ignoreLexiconAliases(false);
		$this->userConfig->ignoreLexiconAliases(false);
	}

	/**
	 * Upgrade stored data in case of changes in the lexicon.
	 * Heavy process to be executed on core and app upgrade.
	 */
	public function updateLexiconEntries(string $appId): void {
		$this->loadConfigServices();
		$this->updateLexiconAppConfigEntries($appId);
		$this->updateLexiconUserConfigEntries($appId);
	}

	/**
	 * Apply modification on the lexicon to the stored app config values:
	 *
	 * - Upgrade AppConfig entries if set as lazy/not-lazy
	 */
	private function updateLexiconAppConfigEntries(string $appId): void {
		$lexicon = $this->appConfig->getConfigDetailsFromLexicon($appId);
		foreach ($lexicon['entries'] as $entry) {
			// update laziness
			$this->appConfig->updateLazy($appId, $entry->getKey(), $entry->isLazy());
		}
	}

	/**
	 * Apply modification on the lexicon to the stored user preferences values:
	 *
	 * - Upgrade UserConfig entries if set as indexed/not-indexed
	 * - Upgrade UserConfig entries if set as lazy/not-lazy
	 */
	private function updateLexiconUserConfigEntries(string $appId): void {
		$lexicon = $this->userConfig->getConfigDetailsFromLexicon($appId);
		foreach ($lexicon['entries'] as $entry) {
			// upgrade based on index flag
			$this->userConfig->updateGlobalIndexed($appId, $entry->getKey(), $entry->isFlagged(IUserConfig::FLAG_INDEXED));
			// update laziness
			$this->userConfig->updateGlobalLazy($appId, $entry->getKey(), $entry->isLazy());
		}
	}

	/**
	 * config services cannot be load at __construct() or install will fail
	 */
	private function loadConfigServices(): void {
		if ($this->appConfig === null) {
			$this->appConfig = Server::get(IAppConfig::class);
		}
		if ($this->userConfig === null) {
			$this->userConfig = Server::get(IUserConfig::class);
		}
	}

	/**
	 * Get details from lexicon related to AppConfig and search for entries with rename to initiate
	 * a migration to new config key
	 */
	private function migrateAppConfigKeys(string $appId): void {
		$lexicon = $this->appConfig->getConfigDetailsFromLexicon($appId);
		foreach ($lexicon['entries'] as $entry) {
			// only interested in entries with rename set
			if ($entry->getRename() === null) {
				continue;
			}

			// only migrate if rename config key has a value and the new config key hasn't
			if ($this->appConfig->hasKey($appId, $entry->getRename())
				&& !$this->appConfig->hasKey($appId, $entry->getKey())) {
				try {
					$this->migrateAppConfigValue($appId, $entry);
				} catch (TypeConflictException $e) {
					$this->logger->error('could not migrate AppConfig value', ['appId' => $appId, 'entry' => $entry, 'exception' => $e]);
					continue;
				}
			}

			// we only delete previous config value if migration went fine.
			$this->appConfig->deleteKey($appId, $entry->getRename());
		}
	}

	/**
	 * Get details from lexicon related to UserConfig and search for entries with rename to initiate
	 * a migration to new config key
	 */
	private function migrateUserConfigKeys(string $appId): void {
		$lexicon = $this->userConfig->getConfigDetailsFromLexicon($appId);
		foreach ($lexicon['entries'] as $entry) {
			// only interested in keys with rename set
			if ($entry->getRename() === null) {
				continue;
			}

			foreach ($this->userConfig->getValuesByUsers($appId, $entry->getRename()) as $userId => $value) {
				if ($this->userConfig->hasKey($userId, $appId, $entry->getKey())) {
					continue;
				}

				try {
					$this->migrateUserConfigValue($userId, $appId, $entry);
				} catch (TypeConflictException $e) {
					$this->logger->error('could not migrate UserConfig value', ['userId' => $userId, 'appId' => $appId, 'entry' => $entry, 'exception' => $e]);
					continue;
				}

				$this->userConfig->deleteUserConfig($userId, $appId, $entry->getRename());
			}
		}
	}


	/**
	 * converting value from rename to the new key
	 *
	 * @throws TypeConflictException if previous value does not fit the expected type
	 */
	private function migrateAppConfigValue(string $appId, Entry $entry): void {
		$value = $this->appConfig->getValueMixed($appId, $entry->getRename(), lazy: null);
		switch ($entry->getValueType()) {
			case ValueType::STRING:
				$this->appConfig->setValueString($appId, $entry->getKey(), $value);
				return;

			case ValueType::INT:
				$this->appConfig->setValueInt($appId, $entry->getKey(), $this->convertToInt($value));
				return;

			case ValueType::FLOAT:
				$this->appConfig->setValueFloat($appId, $entry->getKey(), $this->convertToFloat($value));
				return;

			case ValueType::BOOL:
				$this->appConfig->setValueBool($appId, $entry->getKey(), $this->convertToBool($value, $entry));
				return;

			case ValueType::ARRAY:
				$this->appConfig->setValueArray($appId, $entry->getKey(), $this->convertToArray($value));
				return;
		}
	}

	/**
	 * converting value from rename to the new key
	 *
	 * @throws TypeConflictException if previous value does not fit the expected type
	 */
	private function migrateUserConfigValue(string $userId, string $appId, Entry $entry): void {
		$value = $this->userConfig->getValueMixed($userId, $appId, $entry->getRename(), lazy: null);
		switch ($entry->getValueType()) {
			case ValueType::STRING:
				$this->userConfig->setValueString($userId, $appId, $entry->getKey(), $value);
				return;

			case ValueType::INT:
				$this->userConfig->setValueInt($userId, $appId, $entry->getKey(), $this->convertToInt($value));
				return;

			case ValueType::FLOAT:
				$this->userConfig->setValueFloat($userId, $appId, $entry->getKey(), $this->convertToFloat($value));
				return;

			case ValueType::BOOL:
				$this->userConfig->setValueBool($userId, $appId, $entry->getKey(), $this->convertToBool($value, $entry));
				return;

			case ValueType::ARRAY:
				$this->userConfig->setValueArray($userId, $appId, $entry->getKey(), $this->convertToArray($value));
				return;
		}
	}

	public function convertToInt(string $value): int {
		if (!is_numeric($value) || (float)$value <> (int)$value) {
			throw new TypeConflictException('Value is not an integer');
		}

		return (int)$value;
	}

	public function convertToFloat(string $value): float {
		if (!is_numeric($value)) {
			throw new TypeConflictException('Value is not a float');
		}

		return (float)$value;
	}

	public function convertToBool(string $value, ?Entry $entry = null): bool {
		if (in_array(strtolower($value), ['true', '1', 'on', 'yes'])) {
			$valueBool = true;
		} elseif (in_array(strtolower($value), ['false', '0', 'off', 'no'])) {
			$valueBool = false;
		} else {
			throw new TypeConflictException('Value cannot be converted to boolean');
		}
		if ($entry?->hasOption(Entry::RENAME_INVERT_BOOLEAN) === true) {
			$valueBool = !$valueBool;
		}

		return $valueBool;
	}

	public function convertToArray(string $value): array {
		try {
			$valueArray = json_decode($value, true, flags: JSON_THROW_ON_ERROR);
		} catch (JsonException) {
			throw new TypeConflictException('Value is not a valid json');
		}
		if (!is_array($valueArray)) {
			throw new TypeConflictException('Value is not an array');
		}

		return $valueArray;
	}
}
