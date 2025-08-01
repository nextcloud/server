<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace NCU\Config\Lexicon;

use Closure;
use NCU\Config\ValueType;

/**
 * Model that represent config values within an app config lexicon.
 *
 * @see IConfigLexicon
 * @experimental 31.0.0
 * @deprecated 32.0.0  use \OCP\Config\Lexicon\Entry
 * @see \OCP\Config\Lexicon\Entry
 * @psalm-suppress DeprecatedClass
 */
class ConfigLexiconEntry {
	/**
	 * @experimental 32.0.0
	 * @deprecated 32.0.0  use \OCP\Config\Lexicon\Entry
	 * @see \OCP\Config\Lexicon\Entry
	 */
	public const RENAME_INVERT_BOOLEAN = 1;

	private string $definition = '';
	private ?string $default = null;

	/**
	 * @param string $key config key, can only contain alphanumerical chars and -._
	 * @param ValueType $type type of config value
	 * @param string $definition optional description of config key available when using occ command
	 * @param bool $lazy set config value as lazy
	 * @param int $flags set flags
	 * @param string|null $rename previous config key to migrate config value from
	 * @param bool $deprecated set config key as deprecated
	 *
	 * @experimental 31.0.0
	 * @deprecated 32.0.0  use \OCP\Config\Lexicon\Entry
	 * @see \OCP\Config\Lexicon\Entry
	 * @psalm-suppress PossiblyInvalidCast
	 * @psalm-suppress RiskyCast
	 */
	public function __construct(
		private readonly string $key,
		private readonly ValueType $type,
		private null|string|int|float|bool|array|Closure $defaultRaw = null,
		string $definition = '',
		private readonly bool $lazy = false,
		private readonly int $flags = 0,
		private readonly bool $deprecated = false,
		private readonly ?string $rename = null,
		private readonly int $options = 0,
	) {
		// key can only contain alphanumeric chars and underscore "_"
		if (preg_match('/[^[:alnum:]_]/', $key)) {
			throw new \Exception('invalid config key');
		}

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
	 * @deprecated 32.0.0  use \OCP\Config\Lexicon\Entry
	 * @see \OCP\Config\Lexicon\Entry
	 */
	public function getKey(): string {
		return $this->key;
	}

	/**
	 * get expected type for config value
	 *
	 * @return ValueType
	 * @experimental 31.0.0
	 * @deprecated 32.0.0  use \OCP\Config\Lexicon\Entry
	 * @see \OCP\Config\Lexicon\Entry
	 * @psalm-suppress DeprecatedClass
	 */
	public function getValueType(): ValueType {
		return $this->type;
	}

	/**
	 * @param string $default
	 * @return string
	 * @experimental 31.0.0
	 * @deprecated 32.0.0  use \OCP\Config\Lexicon\Entry
	 * @see \OCP\Config\Lexicon\Entry
	 */
	private function convertFromString(string $default): string {
		return $default;
	}

	/**
	 * @param int $default
	 * @return string
	 * @experimental 31.0.0
	 * @deprecated 32.0.0  use \OCP\Config\Lexicon\Entry
	 * @see \OCP\Config\Lexicon\Entry
	 */
	private function convertFromInt(int $default): string {
		return (string)$default;
	}

	/**
	 * @param float $default
	 * @return string
	 * @experimental 31.0.0
	 * @deprecated 32.0.0  use \OCP\Config\Lexicon\Entry
	 * @see \OCP\Config\Lexicon\Entry
	 */
	private function convertFromFloat(float $default): string {
		return (string)$default;
	}

	/**
	 * @param bool $default
	 * @return string
	 * @experimental 31.0.0
	 * @deprecated 32.0.0  use \OCP\Config\Lexicon\Entry
	 * @see \OCP\Config\Lexicon\Entry
	 */
	private function convertFromBool(bool $default): string {
		return ($default) ? '1' : '0';
	}

	/**
	 * @param array $default
	 * @return string
	 * @experimental 31.0.0
	 * @deprecated 32.0.0  use \OCP\Config\Lexicon\Entry
	 * @see \OCP\Config\Lexicon\Entry
	 */
	private function convertFromArray(array $default): string {
		return json_encode($default);
	}

	/**
	 * returns default value
	 *
	 * @return string|null NULL if no default is set
	 * @experimental 31.0.0
	 * @deprecated 32.0.0  use \OCP\Config\Lexicon\Entry
	 * @see \OCP\Config\Lexicon\Entry
	 * @psalm-suppress DeprecatedClass
	 * @psalm-suppress DeprecatedMethod
	 */
	public function getDefault(Preset $preset): ?string {
		if ($this->default !== null) {
			return $this->default;
		}

		if ($this->defaultRaw === null) {
			return null;
		}

		if ($this->defaultRaw instanceof Closure) {
			/** @psalm-suppress MixedAssignment we expect closure to returns string|int|float|bool|array */
			$this->defaultRaw = ($this->defaultRaw)($preset);
		}

		/** @psalm-suppress MixedArgument closure should be managed previously */
		$this->default = $this->convertToString($this->defaultRaw);

		return $this->default;
	}

	/**
	 * convert $entry into string, based on the expected type for config value
	 *
	 * @param string|int|float|bool|array $entry
	 *
	 * @return string
	 * @experimental 31.0.0
	 * @deprecated 32.0.0  use \OCP\Config\Lexicon\Entry
	 * @see \OCP\Config\Lexicon\Entry
	 * @psalm-suppress PossiblyInvalidCast arrays are managed pre-cast
	 * @psalm-suppress RiskyCast
	 * @psalm-suppress DeprecatedClass
	 * @psalm-suppress DeprecatedMethod
	 * @psalm-suppress DeprecatedConstant
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
	 * @deprecated 32.0.0  use \OCP\Config\Lexicon\Entry
	 * @see \OCP\Config\Lexicon\Entry
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
	 * @deprecated 32.0.0  use \OCP\Config\Lexicon\Entry
	 * @see \OCP\Config\Lexicon\Entry
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
	 * @deprecated 32.0.0  use \OCP\Config\Lexicon\Entry
	 * @see \OCP\Config\Lexicon\Entry
	 */
	public function getFlags(): int {
		return $this->flags;
	}

	/**
	 * @param int $flag
	 *
	 * @return bool TRUE is config value bitflag contains $flag
	 * @experimental 31.0.0
	 * @deprecated 32.0.0  use \OCP\Config\Lexicon\Entry
	 * @see \OCP\Config\Lexicon\Entry
	 * @psalm-suppress DeprecatedMethod
	 */
	public function isFlagged(int $flag): bool {
		return (($flag & $this->getFlags()) === $flag);
	}

	/**
	 * should be called/used only during migration/upgrade.
	 * link to an old config key.
	 *
	 * @return string|null not NULL if value can be imported from a previous key
	 * @experimental 32.0.0
	 * @deprecated 32.0.0  use \OCP\Config\Lexicon\Entry
	 * @see \OCP\Config\Lexicon\Entry
	 */
	public function getRename(): ?string {
		return $this->rename;
	}

	/**
	 * @experimental 32.0.0
	 * @deprecated 32.0.0  use \OCP\Config\Lexicon\Entry
	 * @see \OCP\Config\Lexicon\Entry
	 * @return bool TRUE if $option was set during the creation of the entry.
	 */
	public function hasOption(int $option): bool {
		return (($option & $this->options) !== 0);
	}

	/**
	 * returns if config key is set as deprecated
	 *
	 * @return bool TRUE if config si deprecated
	 * @experimental 31.0.0
	 * @deprecated 32.0.0  use \OCP\Config\Lexicon\Entry
	 * @see \OCP\Config\Lexicon\Entry
	 */
	public function isDeprecated(): bool {
		return $this->deprecated;
	}
}
