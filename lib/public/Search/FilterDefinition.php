<?php

declare(strict_types=1);

/**
 * @copyright 2023 Benjamin Gaussorgues <benjamin.gaussorgues@nextcloud.com>
 *
 * @author Benjamin Gaussorgues <benjamin.gaussorgues@nextcloud.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
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
	public const TYPE_BOOL = 'bool';
	public const TYPE_INT = 'int';
	public const TYPE_FLOAT = 'float';
	public const TYPE_STRING = 'string';
	public const TYPE_STRINGS = 'strings';
	public const TYPE_DATETIME = 'datetime';
	public const TYPE_PERSON = 'person';
	public const TYPE_NC_USER = 'nc-user';
	public const TYPE_NC_GROUP = 'nc-group';

	/**
	 * Build filter definition
	 *
	 * @param self::TYPE_* $type
	 * @param bool $exclusive If true, all providers not supporting this filter will be ignored when this filter is provided
	 * @throw InvalidArgumentException in case of invalid name. Allowed characters are -, 0-9, a-z.
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
