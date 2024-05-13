<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2024 Maxence Lange <maxence@artificial-owl.com>
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 *
 * @license AGPL-3.0 or later
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCP\ConfigLexicon;

/**
 * Model that represent config values within an app config lexicon.
 *
 * @see IConfigLexicon
 * @since 30.0.0
 */
class ConfigLexiconEntry implements IConfigLexiconEntry {
	private string $definition = '';
	private ?string $default = null;

	/**
	 * @param string $key config key
	 * @param ConfigLexiconValueType $type type of config value
	 * @param string $definition optional description of config key available when using occ command
	 * @param bool $lazy set config value as lazy
	 * @param bool $sensitive set config value as sensitive
	 * @param bool $deprecated set config key as deprecated
	 * @since 30.0.0
	 */
	public function __construct(
		private readonly string $key,
		private readonly ConfigLexiconValueType $type,
		null|string|int|float|bool|array $default = null,
		string $definition = '',
		private readonly bool $lazy = false,
		private readonly bool $sensitive = false,
		private readonly bool $deprecated = false
	) {
		if ($default !== null) {
			/** @psalm-suppress InvalidArgument */
			$this->default = match ($type) {
				ConfigLexiconValueType::STRING => $this->convertFromString($default),
				ConfigLexiconValueType::INT => $this->convertFromInt($default),
				ConfigLexiconValueType::FLOAT => $this->convertFromFloat($default),
				ConfigLexiconValueType::BOOL => $this->convertFromBool($default),
				ConfigLexiconValueType::ARRAY => $this->convertFromArray($default)
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
	 * @since 30.0.0
	 */
	public function getKey(): string {
		return $this->key;
	}

	/**
	 * @inheritDoc
	 *
	 * @return ConfigLexiconValueType
	 * @see self::TYPE_STRING and others
	 * @since 30.0.0
	 */
	public function getValueType(): ConfigLexiconValueType {
		return $this->type;
	}

	/**
	 * @param string $default
	 * @return string
	 * @since 30.0.0
	 */
	private function convertFromString(string $default): string {
		return $default;
	}

	/**
	 * @param int $default
	 * @return string
	 * @since 30.0.0
	 */
	private function convertFromInt(int $default): string {
		return (string) $default;
	}

	/**
	 * @param float $default
	 * @return string
	 * @since 30.0.0
	 */
	private function convertFromFloat(float $default): string {
		return (string) $default;
	}

	/**
	 * @param bool $default
	 * @return string
	 * @since 30.0.0
	 */
	private function convertFromBool(bool $default): string {
		return ($default) ? '1' : '0';
	}

	/**
	 * @param array $default
	 * @return string
	 * @since 30.0.0
	 */
	private function convertFromArray(array $default): string {
		return json_encode($default);
	}

	/**
	 * @inheritDoc
	 *
	 * @return string|null NULL if no default is set
	 * @since 30.0.0
	 */
	public function getDefault(): ?string {
		return $this->default;
	}

	/**
	 * @inheritDoc
	 *
	 * @return string
	 * @since 30.0.0
	 */
	public function getDefinition(): string {
		return $this->definition;
	}

	/**
	 * @inheritDoc
	 *
	 * @see IAppConfig for details on lazy config values
	 * @return bool TRUE if config value is lazy
	 * @since 30.0.0
	 */
	public function isLazy(): bool {
		return $this->lazy;
	}

	/**
	 * @inheritDoc
	 *
	 * @see IAppConfig for details on sensitive config values
	 * @return bool TRUE if config value is sensitive
	 * @since 30.0.0
	 */
	public function isSensitive(): bool {
		return $this->sensitive;
	}

	/**
	 * @inheritDoc
	 *
	 * @return bool TRUE if config si deprecated
	 * @since 30.0.0
	 */
	public function isDeprecated(): bool {
		return $this->deprecated;
	}
}
