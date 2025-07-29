<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
			FilterDefinition::TYPE_STRINGS => new Filter\StringsFilter(... (array)$filter),
			default => throw new RuntimeException('Invalid filter type ' . $type),
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
