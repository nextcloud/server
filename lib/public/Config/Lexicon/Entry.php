<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Config\Lexicon;

use Closure;
use OCP\AppFramework\Attribute\Consumable;
use OCP\Config\ValueType;

/**
 * Model that represent config values within an app config lexicon.
 *
 * @see ILexicon
 */
#[Consumable(since: '32.0.0')]
class Entry {
	/** @since 32.0.0 */
	public const RENAME_INVERT_BOOLEAN = 1;

	private ?string $default = null;

	/**
	 * @param string $key config key; can only contain alphanumerical chars and underscore "_"
	 * @param ValueType $type type of config value
	 * @param string|int|float|bool|array|Closure|null $defaultRaw default value to be used in case none known
	 * @param string $definition optional description of config key available when using occ command
	 * @param bool $lazy set config value as lazy
	 * @param int $flags set flags
	 * @param bool $deprecated set config key as deprecated
	 * @param string|null $rename source in case of a rename of a config key.
	 * @param int $options additional bitflag options {@see self::RENAME_INVERT_BOOLEAN}
	 * @param string $note additional note and warning related to the use of the config key.
	 *
	 * @since 32.0.0
	 * @psalm-suppress PossiblyInvalidCast
	 * @psalm-suppress RiskyCast
	 */
	public function __construct(
		private readonly string $key,
		private readonly ValueType $type,
		private null|string|int|float|bool|array|Closure $defaultRaw = null,
		private readonly string $definition = '',
		private readonly bool $lazy = false,
		private readonly int $flags = 0,
		private readonly bool $deprecated = false,
		private readonly ?string $rename = null,
		private readonly int $options = 0,
		private readonly string $note = '',
	) {
		// key can only contain alphanumeric chars and underscore "_"
		if (preg_match('/[^[:alnum:]_]/', $key)) {
			throw new \Exception('invalid config key');
		}
	}

	/**
	 * returns the config key
	 *
	 * @return string config key
	 * @since 32.0.0
	 */
	public function getKey(): string {
		return $this->key;
	}

	/**
	 * get expected type for config value
	 *
	 * @return ValueType
	 * @since 32.0.0
	 */
	public function getValueType(): ValueType {
		return $this->type;
	}

	/**
	 * @param string $default
	 * @return string
	 * @since 32.0.0
	 */
	private function convertFromString(string $default): string {
		return $default;
	}

	/**
	 * @param int $default
	 * @return string
	 * @since 32.0.0
	 */
	private function convertFromInt(int $default): string {
		return (string)$default;
	}

	/**
	 * @param float $default
	 * @return string
	 * @since 32.0.0
	 */
	private function convertFromFloat(float $default): string {
		return (string)$default;
	}

	/**
	 * @param bool $default
	 * @return string
	 * @since 32.0.0
	 */
	private function convertFromBool(bool $default): string {
		return ($default) ? '1' : '0';
	}

	/**
	 * @param array $default
	 * @return string
	 * @since 32.0.0
	 */
	private function convertFromArray(array $default): string {
		return json_encode($default);
	}

	/**
	 * returns default value
	 *
	 * @return string|null NULL if no default is set
	 * @since 32.0.0
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
	 * @since 32.0.0
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
	 * @since 32.0.0
	 */
	public function getDefinition(): string {
		return $this->definition;
	}

	/**
	 * returns eventual note
	 *
	 * @return string
	 * @since 32.0.0
	 */
	public function getNote(): string {
		return $this->note;
	}

	/**
	 * returns if config key is set as lazy
	 *
	 * @see IAppConfig for details on lazy config values
	 * @return bool TRUE if config value is lazy
	 * @since 32.0.0
	 */
	public function isLazy(): bool {
		return $this->lazy;
	}

	/**
	 * returns flags
	 *
	 * @see IAppConfig for details on sensitive config values
	 * @return int bitflag about the config value
	 * @since 32.0.0
	 */
	public function getFlags(): int {
		return $this->flags;
	}

	/**
	 * @param int $flag
	 *
	 * @return bool TRUE is config value bitflag contains $flag
	 * @since 32.0.0
	 */
	public function isFlagged(int $flag): bool {
		return (($flag & $this->getFlags()) === $flag);
	}

	/**
	 * should be called/used only during migration/upgrade.
	 * link to an old config key.
	 *
	 * @return string|null not NULL if value can be imported from a previous key
	 * @since 32.0.0
	 */
	public function getRename(): ?string {
		return $this->rename;
	}

	/**
	 * @since 32.0.0
	 * @return bool TRUE if $option was set during the creation of the entry.
	 */
	public function hasOption(int $option): bool {
		return (($option & $this->options) !== 0);
	}

	/**
	 * returns if config key is set as deprecated
	 *
	 * @return bool TRUE if config si deprecated
	 * @since 32.0.0
	 */
	public function isDeprecated(): bool {
		return $this->deprecated;
	}
}
