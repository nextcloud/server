<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace NCU\Config;

use NCU\Config\Exceptions\IncorrectTypeException;
use OCP\IAppConfig;
use UnhandledMatchError;

/**
 * Listing of available value type for typed config value
 *
 * @experimental 31.0.0
 */
enum ValueType: int {
	/**
	 * @experimental 31.0.0
	 */
	case MIXED = 0;
	/**
	 * @experimental 31.0.0
	 */
	case STRING = 1;
	/**
	 * @experimental 31.0.0
	 */
	case INT = 2;
	/**
	 * @experimental 31.0.0
	 */
	case FLOAT = 3;
	/**
	 * @experimental 31.0.0
	 */
	case BOOL = 4;
	/**
	 * @experimental 31.0.0
	 */
	case ARRAY = 5;

	/**
	 * get ValueType from string
	 *
	 * @param string $definition
	 *
	 * @return self
	 * @throws IncorrectTypeException
	 *
	 * @experimental 31.0.0
	 */
	public static function fromStringDefinition(string $definition): self {
		try {
			return match ($definition) {
				'mixed' => self::MIXED,
				'string' => self::STRING,
				'int' => self::INT,
				'float' => self::FLOAT,
				'bool' => self::BOOL,
				'array' => self::ARRAY
			};
		} catch (\UnhandledMatchError) {
			throw new IncorrectTypeException('unknown string definition');
		}
	}

	/**
	 * get string definition for current enum value
	 *
	 * @return string
	 * @throws IncorrectTypeException
	 *
	 * @experimental 31.0.0
	 */
	public function getDefinition(): string {
		try {
			return match ($this) {
				self::MIXED => 'mixed',
				self::STRING => 'string',
				self::INT => 'int',
				self::FLOAT => 'float',
				self::BOOL => 'bool',
				self::ARRAY => 'array',
			};
		} catch (UnhandledMatchError) {
			throw new IncorrectTypeException('unknown type definition ' . $this->value);
		}
	}

	/**
	 * get corresponding AppConfig flag value
	 *
	 * @return int
	 * @throws IncorrectTypeException
	 *
	 * @experimental 31.0.0
	 */
	public function toAppConfigFlag(): int {
		try {
			return match ($this) {
				self::MIXED => IAppConfig::VALUE_MIXED,
				self::STRING => IAppConfig::VALUE_STRING,
				self::INT => IAppConfig::VALUE_INT,
				self::FLOAT => IAppConfig::VALUE_FLOAT,
				self::BOOL => IAppConfig::VALUE_BOOL,
				self::ARRAY => IAppConfig::VALUE_ARRAY,
			};
		} catch (UnhandledMatchError) {
			throw new IncorrectTypeException('unknown type definition ' . $this->value);
		}
	}

}
