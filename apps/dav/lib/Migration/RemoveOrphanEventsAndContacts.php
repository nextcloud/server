<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Migration;

use OCA\DAV\CalDAV\CalDavBackend;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class RemoveOrphanEventsAndContacts implements IRepairStep {

	public function __construct(
		private IDBConnection $connection,
	) {
	}

	/**
	 * @inheritdoc
	 */
	public function getName(): string {
		return 'Clean up orphan event and contact data';
	}

	/**
	 * @inheritdoc
	 */
	public function run(IOutput $output) {
		$orphanItems = $this->removeOrphanChildren('calendarobjects', 'calendars', 'calendarid');
		$output->info(sprintf('%d events without a calendar have been cleaned up', $orphanItems));
		$orphanItems = $this->removeOrphanChildren('calendarobjects_props', 'calendarobjects', 'objectid');
		$output->info(sprintf('%d properties without an events have been cleaned up', $orphanItems));
		$orphanItems = $this->removeOrphanChildren('calendarchanges', 'calendars', 'calendarid');
		$output->info(sprintf('%d changes without a calendar have been cleaned up', $orphanItems));

		$orphanItems = $this->removeOrphanChildren('calendarobjects', 'calendarsubscriptions', 'calendarid');
		$output->info(sprintf('%d cached events without a calendar subscription have been cleaned up', $orphanItems));
		$orphanItems = $this->removeOrphanChildren('calendarchanges', 'calendarsubscriptions', 'calendarid');
		$output->info(sprintf('%d changes without a calendar subscription have been cleaned up', $orphanItems));

		$orphanItems = $this->removeOrphanChildren('cards', 'addressbooks', 'addressbookid');
		$output->info(sprintf('%d contacts without an addressbook have been cleaned up', $orphanItems));
		$orphanItems = $this->removeOrphanChildren('cards_properties', 'cards', 'cardid');
		$output->info(sprintf('%d properties without a contact have been cleaned up', $orphanItems));
		$orphanItems = $this->removeOrphanChildren('addressbookchanges', 'addressbooks', 'addressbookid');
		$output->info(sprintf('%d changes without an addressbook have been cleaned up', $orphanItems));
	}

	protected function removeOrphanChildren($childTable, $parentTable, $parentId): int {
		$qb = $this->connection->getQueryBuilder();

		$qb->select('c.id')
			->from($childTable, 'c')
			->leftJoin('c', $parentTable, 'p', $qb->expr()->eq('c.' . $parentId, 'p.id'))
			->where($qb->expr()->isNull('p.id'));

		if (\in_array($parentTable, ['calendars', 'calendarsubscriptions'], true)) {
			$calendarType = $parentTable === 'calendarsubscriptions' ? CalDavBackend::CALENDAR_TYPE_SUBSCRIPTION : CalDavBackend::CALENDAR_TYPE_CALENDAR;
			$qb->andWhere($qb->expr()->eq('c.calendartype', $qb->createNamedParameter($calendarType, IQueryBuilder::PARAM_INT), IQueryBuilder::PARAM_INT));
		}

		$result = $qb->executeQuery();

		$orphanItems = [];
		while ($row = $result->fetch()) {
			$orphanItems[] = (int)$row['id'];
		}
		$result->closeCursor();

		if (!empty($orphanItems)) {
			$qb->delete($childTable)
				->where($qb->expr()->in('id', $qb->createParameter('ids')));

			$orphanItemsBatch = array_chunk($orphanItems, 200);
			foreach ($orphanItemsBatch as $items) {
				$qb->setParameter('ids', $items, IQueryBuilder::PARAM_INT_ARRAY);
				$qb->executeStatement();
			}
		}

		return count($orphanItems);
	}
}
