<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
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
		null|string|int|float|bool|array $default = null,
		string $definition = '',
		private readonly bool $lazy = false,
		private readonly int $flags = 0,
		private readonly bool $deprecated = false,
	) {
		if ($default !== null) {
			// in case $default is array but is not expected to be an array...
			$default = ($type !== ValueType::ARRAY && is_array($default)) ? json_encode($default) : $default;
			$this->default = match ($type) {
				ValueType::MIXED => (string)$default,
				ValueType::STRING => $this->convertFromString((string)$default),
				ValueType::INT => $this->convertFromInt((int)$default),
				ValueType::FLOAT => $this->convertFromFloat((float)$default),
				ValueType::BOOL => $this->convertFromBool((bool)$default),
				ValueType::ARRAY => $this->convertFromArray((array)$default)
			};
		}

		/** @psalm-suppress UndefinedClass */
		if (\OC::$CLI) { // only store definition if ran from CLI
			$this->definition = $definition;
		}
	}

	/**
	 * @inheritDoc
	 *
	 * @return string config key
	 * @experimental 31.0.0
	 */
	public function getKey(): string {
		return $this->key;
	}

	/**
	 * @inheritDoc
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
	 * @inheritDoc
	 *
	 * @return string|null NULL if no default is set
	 * @experimental 31.0.0
	 */
	public function getDefault(): ?string {
		return $this->default;
	}

	/**
	 * @inheritDoc
	 *
	 * @return string
	 * @experimental 31.0.0
	 */
	public function getDefinition(): string {
		return $this->definition;
	}

	/**
	 * @inheritDoc
	 *
	 * @see IAppConfig for details on lazy config values
	 * @return bool TRUE if config value is lazy
	 * @experimental 31.0.0
	 */
	public function isLazy(): bool {
		return $this->lazy;
	}

	/**
	 * @inheritDoc
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
		return (bool)($flag & $this->getFlags());
	}

	/**
	 * @inheritDoc
	 *
	 * @return bool TRUE if config si deprecated
	 * @experimental 31.0.0
	 */
	public function isDeprecated(): bool {
		return $this->deprecated;
	}
}
