<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Config;

use JsonException;
use NCU\Config\Exceptions\TypeConflictException;
use NCU\Config\IUserConfig;
use NCU\Config\Lexicon\ConfigLexiconEntry;
use NCU\Config\ValueType;
use OC\AppConfig;
use OCP\App\IAppManager;
use OCP\IAppConfig;
use Psr\Log\LoggerInterface;

class ConfigManager {
	public function __construct(
		/** @var AppConfig $appConfig */
		private readonly IAppConfig $appConfig,
		/** @var UserConfig $appConfig */
		private readonly IUserConfig $userConfig,
		private readonly IAppManager $appManager,
		private readonly LoggerInterface $logger,
	) {
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
	 * @see ConfigLexiconEntry
	 * @internal
	 * @param string|null $appId when set to NULL the method will be executed for all enabled apps of the instance
	 */
	public function migrateConfigLexiconKeys(?string $appId = null): void {
		if ($appId === null) {
			$this->migrateConfigLexiconKeys('core');
			foreach ($this->appManager->getEnabledApps() as $app) {
				$this->migrateConfigLexiconKeys($app);
			}

			return;
		}

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
	 * Get details from lexicon related to AppConfig and search for entries with rename to initiate
	 * a migration to new config key
	 */
	private function migrateAppConfigKeys(string $appId): void {
		$lexicon = $this->appConfig->getConfigDetailsFromLexicon($appId);

		// we store a list of config keys to compare with any 'copyFrom'
		$keys = [];
		foreach ($lexicon['entries'] as $entry) {
			$keys[] = $entry->getKey();
		}

		foreach ($lexicon['entries'] as $entry) {
			// only interested in entries with rename set
			if ($entry->getRename() === null) {
				continue;
			}

			if (in_array($entry->getRename(), $keys, true)) {
				$this->logger->error('rename value should not exist as a valid config key within Lexicon');
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

		// we store a list of set keys to compare with any 'copyFrom'
		$keys = [];
		foreach ($lexicon['entries'] as $entry) {
			$keys[] = $entry->getKey();
		}

		foreach ($lexicon['entries'] as $entry) {
			// only interested in keys with rename set
			if ($entry->getRename() === null) {
				continue;
			}

			if (in_array($entry->getRename(), $keys, true)) {
				$this->logger->error('rename value should not exist as a valid key within Lexicon');
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
	private function migrateAppConfigValue(string $appId, ConfigLexiconEntry $entry): void {
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
	private function migrateUserConfigValue(string $userId, string $appId, ConfigLexiconEntry $entry): void {
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
		if ($value !== ((string)((int)$value))) {
			throw new TypeConflictException('Value is not an integer');
		}

		return (int)$value;
	}

	public function convertToFloat(string $value): float {
		if ($value !== ((string)((float)$value))) {
			throw new TypeConflictException('Value is not a float');
		}

		return (float)$value;
	}

	public function convertToBool(string $value, ?ConfigLexiconEntry $entry = null): bool {
		if (in_array(strtolower($value), ['true', '1', 'on', 'yes'])) {
			$valueBool = true;
		} elseif (in_array(strtolower($value), ['false', '0', 'off', 'no'])) {
			$valueBool = false;
		} else {
			throw new TypeConflictException('Value cannot be converted to boolean');
		}
		if ($entry?->hasOption(ConfigLexiconEntry::RENAME_INVERT_BOOLEAN) === true) {
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
