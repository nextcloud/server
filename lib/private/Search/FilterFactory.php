<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Search;

use OC\Search\Filter\BooleanFilter;
use OC\Search\Filter\DateTimeFilter;
use OC\Search\Filter\FloatFilter;
use OC\Search\Filter\GroupFilter;
use OC\Search\Filter\IntegerFilter;
use OC\Search\Filter\StringFilter;
use OC\Search\Filter\StringsFilter;
use OC\Search\Filter\UserFilter;
use OCP\IGroupManager;
use OCP\IUserManager;
use OCP\Search\FilterDefinition;
use OCP\Search\IFilter;
use OCP\Server;
use RuntimeException;

final class FilterFactory {
	private const PERSON_TYPE_SEPARATOR = '/';

	public static function get(string $type, string|array $filter): IFilter {
		return match ($type) {
			FilterDefinition::TYPE_BOOL => new BooleanFilter($filter),
			FilterDefinition::TYPE_DATETIME => new DateTimeFilter($filter),
			FilterDefinition::TYPE_FLOAT => new FloatFilter($filter),
			FilterDefinition::TYPE_INT => new IntegerFilter($filter),
			FilterDefinition::TYPE_NC_GROUP => new GroupFilter($filter, Server::get(IGroupManager::class)),
			FilterDefinition::TYPE_NC_USER => new UserFilter($filter, Server::get(IUserManager::class)),
			FilterDefinition::TYPE_PERSON => self::getPerson($filter),
			FilterDefinition::TYPE_STRING => new StringFilter($filter),
			FilterDefinition::TYPE_STRINGS => new StringsFilter(... (array)$filter),
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
