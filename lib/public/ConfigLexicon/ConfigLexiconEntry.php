<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCP\ConfigLexicon;

/**
 * Model that represent config values within an app config lexicon.
 *
 * @see IConfigLexicon
 * @since 31.0.0
 */
class ConfigLexiconEntry implements IConfigLexiconEntry {
	private string $definition = '';
	private ?string $default = null;

	/**
	 * @param string $key config key
	 * @param ValueType $type type of config value
	 * @param string $definition optional description of config key available when using occ command
	 * @param bool $lazy set config value as lazy
	 * @param bool $sensitive set config value as sensitive
	 * @param bool $deprecated set config key as deprecated
	 *
	 * @since 31.0.0
	 */
	public function __construct(
		private readonly string $key,
		private readonly ValueType $type,
		null|string|int|float|bool|array $default = null,
		string $definition = '',
		private readonly bool $lazy = false,
		private readonly bool $sensitive = false,
		private readonly bool $deprecated = false
	) {
		if ($default !== null) {
			try {
				/** @psalm-suppress InvalidArgument */
				$this->default = match ($type) {
					ValueType::STRING => $this->convertFromString($default),
					ValueType::INT => $this->convertFromInt($default),
					ValueType::FLOAT => $this->convertFromFloat($default),
					ValueType::BOOL => $this->convertFromBool($default),
					ValueType::ARRAY => $this->convertFromArray($default)
				};
			} catch (\TypeError) {
				// the idea is that the $default value should be typed as set by the $type argument
				$this->default = (string) $default;
			}
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
	 * @since 31.0.0
	 */
	public function getKey(): string {
		return $this->key;
	}

	/**
	 * @inheritDoc
	 *
	 * @return ValueType
	 * @see self::TYPE_STRING and others
	 * @since 31.0.0
	 */
	public function getValueType(): ValueType {
		return $this->type;
	}

	/**
	 * @param string $default
	 * @return string
	 * @since 31.0.0
	 */
	private function convertFromString(string $default): string {
		return $default;
	}

	/**
	 * @param int $default
	 * @return string
	 * @since 31.0.0
	 */
	private function convertFromInt(int $default): string {
		return (string) $default;
	}

	/**
	 * @param float $default
	 * @return string
	 * @since 31.0.0
	 */
	private function convertFromFloat(float $default): string {
		return (string) $default;
	}

	/**
	 * @param bool $default
	 * @return string
	 * @since 31.0.0
	 */
	private function convertFromBool(bool $default): string {
		return ($default) ? '1' : '0';
	}

	/**
	 * @param array $default
	 * @return string
	 * @since 31.0.0
	 */
	private function convertFromArray(array $default): string {
		return json_encode($default);
	}

	/**
	 * @inheritDoc
	 *
	 * @return string|null NULL if no default is set
	 * @since 31.0.0
	 */
	public function getDefault(): ?string {
		return $this->default;
	}

	/**
	 * @inheritDoc
	 *
	 * @return string
	 * @since 31.0.0
	 */
	public function getDefinition(): string {
		return $this->definition;
	}

	/**
	 * @inheritDoc
	 *
	 * @see IAppConfig for details on lazy config values
	 * @return bool TRUE if config value is lazy
	 * @since 31.0.0
	 */
	public function isLazy(): bool {
		return $this->lazy;
	}

	/**
	 * @inheritDoc
	 *
	 * @see IAppConfig for details on sensitive config values
	 * @return bool TRUE if config value is sensitive
	 * @since 31.0.0
	 */
	public function isSensitive(): bool {
		return $this->sensitive;
	}

	/**
	 * @inheritDoc
	 *
	 * @return bool TRUE if config si deprecated
	 * @since 31.0.0
	 */
	public function isDeprecated(): bool {
		return $this->deprecated;
	}
}
