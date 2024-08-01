<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Repair\NC11;

use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class FixMountStorages implements IRepairStep {
	/** @var IDBConnection */
	private $db;

	/**
	 * @param IDBConnection $db
	 */
	public function __construct(IDBConnection $db) {
		$this->db = $db;
	}

	/**
	 * @return string
	 */
	public function getName() {
		return 'Fix potential broken mount points';
	}

	public function run(IOutput $output) {
		$query = $this->db->getQueryBuilder();
		$query->select('m.id', 'f.storage')
			->from('mounts', 'm')
			->leftJoin('m', 'filecache', 'f', $query->expr()->eq('m.root_id', 'f.fileid'))
			->where($query->expr()->neq('m.storage_id', 'f.storage'));

		$update = $this->db->getQueryBuilder();
		$update->update('mounts')
			->set('storage_id', $update->createParameter('storage'))
			->where($query->expr()->eq('id', $update->createParameter('mount')));

		$result = $query->execute();
		$entriesUpdated = 0;
		while ($row = $result->fetch()) {
			$update->setParameter('storage', $row['storage'], IQueryBuilder::PARAM_INT)
				->setParameter('mount', $row['id'], IQueryBuilder::PARAM_INT);
			$update->execute();
			$entriesUpdated++;
		}
		$result->closeCursor();

		if ($entriesUpdated > 0) {
			$output->info($entriesUpdated . ' mounts updated');
			return;
		}

		$output->info('No mounts updated');
	}
}
