<?php
/**
 * Copyright (c) 2015 Joas Schilling <nickvergessen@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Repair;

use OC\DB\Connection;
use OC\Hooks\BasicEmitter;
use OC\RepairStep;

/**
 * Class RepairConfig
 *
 * @package OC\Repair
 */
class CleanTags extends BasicEmitter implements RepairStep {

	/** @var Connection */
	protected $connection;

	/**
	 * @param Connection $connection
	 */
	public function __construct(Connection $connection) {
		$this->connection = $connection;
	}

	/**
	 * @return string
	 */
	public function getName() {
		return 'Clean tags and favorites';
	}

	/**
	 * Updates the configuration after running an update
	 */
	public function run() {
		$this->deleteOrphanFileEntries();
		$this->deleteOrphanTagEntries();
		$this->deleteOrphanCategoryEntries();
	}

	/**
	 * Delete tag entries for deleted files
	 */
	protected function deleteOrphanFileEntries() {
		$this->deleteOrphanEntries(
			'%d tags for delete files have been removed.',
			'*PREFIX*vcategory_to_object', 'objid',
			'*PREFIX*filecache', 'fileid', 'path_hash'
		);
	}

	/**
	 * Delete tag entries for deleted tags
	 */
	protected function deleteOrphanTagEntries() {
		$this->deleteOrphanEntries(
			'%d tag entries for deleted tags have been removed.',
			'*PREFIX*vcategory_to_object', 'categoryid',
			'*PREFIX*vcategory', 'id', 'uid'
		);
	}

	/**
	 * Delete tags that have no entries
	 */
	protected function deleteOrphanCategoryEntries() {
		$this->deleteOrphanEntries(
			'%d tags with no entries have been removed.',
			'*PREFIX*vcategory', 'id',
			'*PREFIX*vcategory_to_object', 'categoryid', 'type'
		);
	}

	/**
	 * Deletes all entries from $deleteTable that do not have a matching entry in $sourceTable
	 *
	 * A query joins $deleteTable.$deleteId = $sourceTable.$sourceId and checks
	 * whether $sourceNullColumn is null. If it is null, the entry in $deleteTable
	 * is being deleted.
	 *
	 * @param string $repairInfo
	 * @param string $deleteTable
	 * @param string $deleteId
	 * @param string $sourceTable
	 * @param string $sourceId
	 * @param string $sourceNullColumn	If this column is null in the source table,
	 * 								the entry is deleted in the $deleteTable
	 */
	protected function deleteOrphanEntries($repairInfo, $deleteTable, $deleteId, $sourceTable, $sourceId, $sourceNullColumn) {
		$qb = $this->connection->createQueryBuilder();

		$qb->select('d.`' . $deleteId . '`')
			->from('`' . $deleteTable . '`', 'd')
			->leftJoin('d', '`' . $sourceTable . '`', 's', 'd.`' . $deleteId . '` = s.`' . $sourceId . '`')
			->where(
				'd.`type` = ' . $qb->expr()->literal('files')
			)
			->andWhere(
				$qb->expr()->isNull('s.`' . $sourceNullColumn . '`')
			);
		$result = $qb->execute();

		$orphanItems = array();
		while ($row = $result->fetch()) {
			$orphanItems[] = (int) $row[$deleteId];
		}

		if (!empty($orphanItems)) {
			$orphanItemsBatch = array_chunk($orphanItems, 200);
			foreach ($orphanItemsBatch as $items) {
				$qb->delete('`' . $deleteTable . '`')
					->where(
						'`type` = ' . $qb->expr()->literal('files')
					)
					->andWhere($qb->expr()->in('`' . $deleteId . '`', ':ids'));
				$qb->setParameter('ids', $items, \Doctrine\DBAL\Connection::PARAM_INT_ARRAY);
				$qb->execute();
			}
		}

		if ($repairInfo) {
			$this->emit('\OC\Repair', 'info', array(sprintf($repairInfo, sizeof($orphanItems))));
		}
	}
}
