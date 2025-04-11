<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace NCU\Config\Lexicon;

use NCU\Config\ValueType;

/**
 * Model that represent config values within an app config lexicon.
 *
 * @see IConfigLexicon
 * @experimental 31.0.0
 */
class ConfigLexiconEntry {
	private string $definition = '';
	private ?string $default = null;

	/**
	 * @param string $key config key
	 * @param ValueType $type type of config value
	 * @param string $definition optional description of config key available when using occ command
	 * @param bool $lazy set config value as lazy
	 * @param int $flags set flags
	 * @param bool $deprecated set config key as deprecated
	 *
	 * @experimental 31.0.0
	 * @psalm-suppress PossiblyInvalidCast
	 * @psalm-suppress RiskyCast
	 */
	public function __construct(
		private readonly string $key,
		private readonly ValueType $type,
		private null|string|int|float|bool|array $defaultRaw = null,
		string $definition = '',
		private readonly bool $lazy = false,
		private readonly int $flags = 0,
		private readonly bool $deprecated = false,
	) {
		/** @psalm-suppress UndefinedClass */
		if (\OC::$CLI) { // only store definition if ran from CLI
			$this->definition = $definition;
		}
	}

	/**
	 * returns the config key
	 *
	 * @return string config key
	 * @experimental 31.0.0
	 */
	public function getKey(): string {
		return $this->key;
	}

	/**
	 * get expected type for config value
	 *
	 * @return ValueType
	 * @experimental 31.0.0
	 */
	public function getValueType(): ValueType {
		return $this->type;
	}

	/**
	 * @param string $default
	 * @return string
	 * @experimental 31.0.0
	 */
	private function convertFromString(string $default): string {
		return $default;
	}

	/**
	 * @param int $default
	 * @return string
	 * @experimental 31.0.0
	 */
	private function convertFromInt(int $default): string {
		return (string)$default;
	}

	/**
	 * @param float $default
	 * @return string
	 * @experimental 31.0.0
	 */
	private function convertFromFloat(float $default): string {
		return (string)$default;
	}

	/**
	 * @param bool $default
	 * @return string
	 * @experimental 31.0.0
	 */
	private function convertFromBool(bool $default): string {
		return ($default) ? '1' : '0';
	}

	/**
	 * @param array $default
	 * @return string
	 * @experimental 31.0.0
	 */
	private function convertFromArray(array $default): string {
		return json_encode($default);
	}

	/**
	 * returns default value
	 *
	 * @return string|null NULL if no default is set
	 * @experimental 31.0.0
	 */
	public function getDefault(): ?string {
		if ($this->defaultRaw === null) {
			return null;
		}

		if ($this->default === null) {
			$this->default = $this->convertToString($this->defaultRaw);
		}

		return $this->default;
	}

	/**
	 * convert $entry into string, based on the expected type for config value
	 *
	 * @param string|int|float|bool|array $entry
	 *
	 * @return string
	 * @experimental 31.0.0
	 * @psalm-suppress PossiblyInvalidCast arrays are managed pre-cast
	 * @psalm-suppress RiskyCast
	 */
	public function convertToString(string|int|float|bool|array $entry): string {
		// in case $default is array but is not expected to be an array...
		if ($this->getValueType() !== ValueType::ARRAY && is_array($entry)) {
			$entry = json_encode($entry, JSON_THROW_ON_ERROR);
		}

		return match ($this->getValueType()) {
			ValueType::MIXED => (string)$entry,
			ValueType::STRING => $this->convertFromString((string)$entry),
			ValueType::INT => $this->convertFromInt((int)$entry),
			ValueType::FLOAT => $this->convertFromFloat((float)$entry),
			ValueType::BOOL => $this->convertFromBool((bool)$entry),
			ValueType::ARRAY => $this->convertFromArray((array)$entry)
		};
	}

	/**
	 * returns definition
	 *
	 * @return string
	 * @experimental 31.0.0
	 */
	public function getDefinition(): string {
		return $this->definition;
	}

	/**
	 * returns if config key is set as lazy
	 *
	 * @see IAppConfig for details on lazy config values
	 * @return bool TRUE if config value is lazy
	 * @experimental 31.0.0
	 */
	public function isLazy(): bool {
		return $this->lazy;
	}

	/**
	 * returns flags
	 *
	 * @see IAppConfig for details on sensitive config values
	 * @return int bitflag about the config value
	 * @experimental 31.0.0
	 */
	public function getFlags(): int {
		return $this->flags;
	}

	/**
	 * @param int $flag
	 *
	 * @return bool TRUE is config value bitflag contains $flag
	 * @experimental 31.0.0
	 */
	public function isFlagged(int $flag): bool {
		return (($flag & $this->getFlags()) === $flag);
	}

	/**
	 * returns if config key is set as deprecated
	 *
	 * @return bool TRUE if config si deprecated
	 * @experimental 31.0.0
	 */
	public function isDeprecated(): bool {
		return $this->deprecated;
	}
}
