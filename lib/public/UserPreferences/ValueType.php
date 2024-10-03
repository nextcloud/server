<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\UserPreferences;

use OCP\UserPreferences\Exceptions\IncorrectTypeException;
use UnhandledMatchError;
use ValueError;

/**
 * Listing of available value type for user preferences
 *
 * @see IUserPreferences
 * @since 31.0.0
 */
enum ValueType: int {
	/** @since 31.0.0 */
	case SENSITIVE = 1;
	/** @since 31.0.0 */
	case MIXED = 2;
	/** @since 31.0.0 */
	case STRING = 4;
	/** @since 31.0.0 */
	case INT = 8;
	/** @since 31.0.0 */
	case FLOAT = 16;
	/** @since 31.0.0 */
	case BOOL = 32;
	/** @since 31.0.0 */
	case ARRAY = 64;

	/**
	 * get ValueType from string based on ValueTypeDefinition
	 *
	 * @param string $definition
	 *
	 * @return self
	 * @throws IncorrectTypeException
	 * @since 31.0.0
	 */
	public function fromStringDefinition(string $definition): self {
		try {
			return $this->fromValueDefinition(ValueTypeDefinition::from($definition));
		} catch (ValueError) {
			throw new IncorrectTypeException('unknown string definition');
		}
	}

	/**
	 * get ValueType from ValueTypeDefinition
	 *
	 * @param ValueTypeDefinition $definition
	 *
	 * @return self
	 * @throws IncorrectTypeException
	 * @since 31.0.0
	 */
	public function fromValueDefinition(ValueTypeDefinition $definition): self {
		try {
			return match ($definition) {
				ValueTypeDefinition::MIXED => self::MIXED,
				ValueTypeDefinition::STRING => self::STRING,
				ValueTypeDefinition::INT => self::INT,
				ValueTypeDefinition::FLOAT => self::FLOAT,
				ValueTypeDefinition::BOOL => self::BOOL,
				ValueTypeDefinition::ARRAY => self::ARRAY
			};
		} catch (UnhandledMatchError) {
			throw new IncorrectTypeException('unknown definition ' . $definition->value);
		}
	}

	/**
	 * get string definition for current enum value
	 *
	 * @return string
	 * @throws IncorrectTypeException
	 * @since 31.0.0
	 */
	public function getDefinition(): string {
		return $this->getValueTypeDefinition()->value;
	}

	/**
	 * get ValueTypeDefinition for current enum value
	 *
	 * @return ValueTypeDefinition
	 * @throws IncorrectTypeException
	 * @since 31.0.0
	 */
	public function getValueTypeDefinition(): ValueTypeDefinition {
		try {
			/** @psalm-suppress UnhandledMatchCondition */
			return match ($this) {
				self::MIXED => ValueTypeDefinition::MIXED,
				self::STRING => ValueTypeDefinition::STRING,
				self::INT => ValueTypeDefinition::INT,
				self::FLOAT => ValueTypeDefinition::FLOAT,
				self::BOOL => ValueTypeDefinition::BOOL,
				self::ARRAY => ValueTypeDefinition::ARRAY,
			};
		} catch (UnhandledMatchError) {
			throw new IncorrectTypeException('unknown type definition ' . $this->value);
		}
	}
}
