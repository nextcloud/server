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
namespace OC\Search;

use OCP\IGroupManager;
use OCP\IUserManager;
use OCP\Search\FilterDefinition;
use OCP\Search\IFilter;
use RuntimeException;

final class FilterFactory {
	private const PERSON_TYPE_SEPARATOR = '/';

	public static function get(string $type, string|array $filter): IFilter {
		return match ($type) {
			FilterDefinition::TYPE_BOOL => new Filter\BooleanFilter($filter),
			FilterDefinition::TYPE_DATETIME => new Filter\DateTimeFilter($filter),
			FilterDefinition::TYPE_FLOAT => new Filter\FloatFilter($filter),
			FilterDefinition::TYPE_INT => new Filter\IntegerFilter($filter),
			FilterDefinition::TYPE_NC_GROUP => new Filter\GroupFilter($filter, \OC::$server->get(IGroupManager::class)),
			FilterDefinition::TYPE_NC_USER => new Filter\UserFilter($filter, \OC::$server->get(IUserManager::class)),
			FilterDefinition::TYPE_PERSON => self::getPerson($filter),
			FilterDefinition::TYPE_STRING => new Filter\StringFilter($filter),
			FilterDefinition::TYPE_STRINGS => new Filter\StringsFilter(... (array) $filter),
			default => throw new RuntimeException('Invalid filter type '. $type),
		};
	}

	private static function getPerson(string $person): IFilter {
		$parts = explode(self::PERSON_TYPE_SEPARATOR, $person, 2);

		return match (count($parts)) {
			1 => self::get(FilterDefinition::TYPE_NC_USER, $person),
			2 => self::get(... $parts),
		};
	}
}
