<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Db;

use OCP\AppFramework\Db\QBMapper;
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

}
