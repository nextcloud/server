<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Tagging;

use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/**
 * @template-extends QBMapper<TagRelation>
 */
class TagRelationMapper extends QBMapper {

	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'vcategory_to_object', TagRelation::class);
	}

	public function deleteByObjidAndTagIds(int $objid, array $tagIds): void {
		$qb = $this->db->getQueryBuilder();
		$qb->delete($this->getTableName())
			->where($qb->expr()->eq('objid', $qb->createNamedParameter($objid, IQueryBuilder::PARAM_INT), IQueryBuilder::PARAM_INT))
			->andWhere($qb->expr()->in('categoryid', $qb->createNamedParameter($tagIds, IQueryBuilder::PARAM_INT_ARRAY)), IQueryBuilder::PARAM_INT_ARRAY);

		$qb->executeStatement();
	}
}
