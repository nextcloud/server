<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Db;

use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/**
 * @template-extends QBMapper<Property>
 */
class PropertyMapper extends QBMapper {

	private const TABLE_NAME = 'properties';

	public function __construct(IDBConnection $db) {
		parent::__construct($db, self::TABLE_NAME, Property::class);
	}

	/**
	 * @return Property[]
	 */
	public function findPropertyByPathAndName(string $userId, string $path, string $name): array {
		$selectQb = $this->db->getQueryBuilder();
		$selectQb->select('*')
			->from(self::TABLE_NAME)
			->where(
				$selectQb->expr()->eq('userid', $selectQb->createNamedParameter($userId)),
				$selectQb->expr()->eq('propertypath', $selectQb->createNamedParameter($path)),
				$selectQb->expr()->eq('propertyname', $selectQb->createNamedParameter($name)),
			);
		return $this->findEntities($selectQb);
	}

	/**
	 * @param array<string, string[]> $calendars
	 * @return Property[]
	 * @throws \OCP\DB\Exception
	 */
	public function findPropertiesByPathsAndUsers(array $calendars): array {
		if (empty($calendars)) {
			return [];
		}
		$selectQb = $this->db->getQueryBuilder();
		$selectQb->select('*')
			->from(self::TABLE_NAME);

		foreach ($calendars as $user => $paths) {
			$selectQb->orWhere(
				$selectQb->expr()->andX(
					$selectQb->expr()->eq('userid', $selectQb->createNamedParameter($user)),
					$selectQb->expr()->in('propertypath', $selectQb->createNamedParameter($paths, IQueryBuilder::PARAM_STR_ARRAY)),
				)
			);
		}

		return $this->findEntities($selectQb);
	}

	/**
	 * @param string[] $calendars
	 * @param string[] $allowedProperties
	 * @return Property[]
	 * @throws \OCP\DB\Exception
	 */
	public function findPropertiesByPaths(array $calendars, array $allowedProperties = []): array {
		$selectQb = $this->db->getQueryBuilder();
		$selectQb->select('*')
			->from(self::TABLE_NAME)
			->where($selectQb->expr()->in('propertypath', $selectQb->createNamedParameter($calendars, IQueryBuilder::PARAM_STR_ARRAY)));

		if ($allowedProperties) {
			$selectQb->andWhere($selectQb->expr()->in('propertyname', $selectQb->createNamedParameter($allowedProperties, IQueryBuilder::PARAM_STR_ARRAY)));
		}

		return $this->findEntities($selectQb);
	}
}
