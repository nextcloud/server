<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Repair;

use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;
use Override;

class RemoveBrokenProperties implements IRepairStep {
	public function __construct(
		private readonly IDBConnection $db,
	) {
	}

	#[Override]
	public function getName(): string {
		return 'Remove broken DAV object properties';
	}

	#[Override]
	public function run(IOutput $output): void {
		// retrieve all object properties
		$qb = $this->db->getQueryBuilder();
		$qb->select('id', 'propertyvalue')
			->from('properties')
			->where($qb->expr()->eq('valuetype', $qb->createNamedParameter('3', IQueryBuilder::PARAM_INT), IQueryBuilder::PARAM_INT));
		$result = $qb->executeQuery();
		// find broken object properties
		$brokenIds = [];
		while ($entry = $result->fetch()) {
			if (!empty($entry['propertyvalue'])) {
				$object = @unserialize(str_replace('\x00', chr(0), $entry['propertyvalue']));
				if ($object === false) {
					$brokenIds[] = $entry['id'];
				}
			} else {
				$brokenIds[] = $entry['id'];
			}
		}
		$result->closeCursor();
		// delete broken object properties
		$qb = $this->db->getQueryBuilder();
		$qb->delete('properties')
			->where($qb->expr()->in('id', $qb->createParameter('ids'), IQueryBuilder::PARAM_STR_ARRAY));
		foreach (array_chunk($brokenIds, 1000) as $chunkIds) {
			$qb->setParameter('ids', $chunkIds, IQueryBuilder::PARAM_STR_ARRAY);
			$qb->executeStatement();
		}
		$total = count($brokenIds);
		$output->info("$total broken object properties removed");
	}
}
