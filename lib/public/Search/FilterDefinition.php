<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Search;

use InvalidArgumentException;

/**
 * Filter definition
 *
 * Describe filter attributes
 *
 * @since 28.0.0
 */
class FilterDefinition {
	/**
	 * @since 28.0.0
	 */
	public const TYPE_BOOL = 'bool';

	/**
	 * @since 28.0.0
	 */
	public const TYPE_INT = 'int';

	/**
	 * @since 28.0.0
	 */
	public const TYPE_FLOAT = 'float';

	/**
	 * @since 28.0.0
	 */
	public const TYPE_STRING = 'string';

	/**
	 * @since 28.0.0
	 */
	public const TYPE_STRINGS = 'strings';

	/**
	 * @since 28.0.0
	 */
	public const TYPE_DATETIME = 'datetime';

	/**
	 * @since 28.0.0
	 */
	public const TYPE_PERSON = 'person';

	/**
	 * @since 28.0.0
	 */
	public const TYPE_NC_USER = 'nc-user';

	/**
	 * @since 28.0.0
	 */
	public const TYPE_NC_GROUP = 'nc-group';

	/**
	 * Build filter definition
	 *
	 * @param self::TYPE_* $type
	 * @param bool $exclusive If true, all providers not supporting this filter will be ignored when this filter is provided
	 * @throws InvalidArgumentException in case of invalid name. Allowed characters are -, 0-9, a-z.
	 * @since 28.0.0
	 */
	public function __construct(
		private string $name,
		private string $type = self::TYPE_STRING,
		private bool $exclusive = true,
	) {
		if (!preg_match('/[-0-9a-z]+/Au', $name)) {
			throw new InvalidArgumentException('Invalid filter name. Allowed characters are [-0-9a-z]');
		}
	}

	/**
	 * Filter name
	 *
	 * Name is used in query string and for advanced syntax `name: <value>`
	 *
	 * @since 28.0.0
	 */
	public function name(): string {
		return $this->name;
	}

	/**
	 * Filter type
	 *
	 * Expected type of value for the filter
	 *
	 * @return self::TYPE_*
	 * @since 28.0.0
	 */
	public function type(): string {
		return $this->type;
	}

	/**
	 * Is filter exclusive?
	 *
	 * If exclusive, only provider with support for this filter will receive the query.
	 * Example: if an exclusive filter `mimetype` is declared, a search with this term will not
	 * be send to providers like `settings` that doesn't support it.
	 *
	 * @since 28.0.0
	 */
	public function exclusive(): bool {
		return $this->exclusive;
	}
}
