<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Migration;

use OCA\DAV\CalDAV\CalDavBackend;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class RemoveClassifiedEventActivity implements IRepairStep {

	public function __construct(
		private IDBConnection $connection,
	) {
	}

	/**
	 * @inheritdoc
	 */
	public function getName() {
		return 'Remove activity entries of private events';
	}

	/**
	 * @inheritdoc
	 */
	public function run(IOutput $output) {
		if (!$this->connection->tableExists('activity')) {
			return;
		}

		$deletedEvents = $this->removePrivateEventActivity();
		$deletedEvents += $this->removeConfidentialUncensoredEventActivity();

		$output->info("Removed $deletedEvents activity entries");
	}

	protected function removePrivateEventActivity(): int {
		$deletedEvents = 0;

		$delete = $this->connection->getQueryBuilder();
		$delete->delete('activity')
			->where($delete->expr()->neq('affecteduser', $delete->createParameter('owner')))
			->andWhere($delete->expr()->eq('object_type', $delete->createParameter('type')))
			->andWhere($delete->expr()->eq('object_id', $delete->createParameter('calendar_id')))
			->andWhere($delete->expr()->like('subjectparams', $delete->createParameter('event_uid')));

		$query = $this->connection->getQueryBuilder();
		$query->select('c.principaluri', 'o.calendarid', 'o.uid')
			->from('calendarobjects', 'o')
			->leftJoin('o', 'calendars', 'c', $query->expr()->eq('c.id', 'o.calendarid'))
			->where($query->expr()->eq('o.classification', $query->createNamedParameter(CalDavBackend::CLASSIFICATION_PRIVATE)));
		$result = $query->executeQuery();

		while ($row = $result->fetch()) {
			if ($row['principaluri'] === null) {
				continue;
			}

			$delete->setParameter('owner', $this->getPrincipal($row['principaluri']))
				->setParameter('type', 'calendar')
				->setParameter('calendar_id', $row['calendarid'])
				->setParameter('event_uid', '%' . $this->connection->escapeLikeParameter('{"id":"' . $row['uid'] . '"') . '%');
			$deletedEvents += $delete->executeStatement();
		}
		$result->closeCursor();

		return $deletedEvents;
	}

	protected function removeConfidentialUncensoredEventActivity(): int {
		$deletedEvents = 0;

		$delete = $this->connection->getQueryBuilder();
		$delete->delete('activity')
			->where($delete->expr()->neq('affecteduser', $delete->createParameter('owner')))
			->andWhere($delete->expr()->eq('object_type', $delete->createParameter('type')))
			->andWhere($delete->expr()->eq('object_id', $delete->createParameter('calendar_id')))
			->andWhere($delete->expr()->like('subjectparams', $delete->createParameter('event_uid')))
			->andWhere($delete->expr()->notLike('subjectparams', $delete->createParameter('filtered_name')));

		$query = $this->connection->getQueryBuilder();
		$query->select('c.principaluri', 'o.calendarid', 'o.uid')
			->from('calendarobjects', 'o')
			->leftJoin('o', 'calendars', 'c', $query->expr()->eq('c.id', 'o.calendarid'))
			->where($query->expr()->eq('o.classification', $query->createNamedParameter(CalDavBackend::CLASSIFICATION_CONFIDENTIAL)));
		$result = $query->executeQuery();

		while ($row = $result->fetch()) {
			if ($row['principaluri'] === null) {
				continue;
			}

			$delete->setParameter('owner', $this->getPrincipal($row['principaluri']))
				->setParameter('type', 'calendar')
				->setParameter('calendar_id', $row['calendarid'])
				->setParameter('event_uid', '%' . $this->connection->escapeLikeParameter('{"id":"' . $row['uid'] . '"') . '%')
				->setParameter('filtered_name', '%' . $this->connection->escapeLikeParameter('{"id":"' . $row['uid'] . '","name":"Busy"') . '%');
			$deletedEvents += $delete->executeStatement();
		}
		$result->closeCursor();

		return $deletedEvents;
	}

	protected function getPrincipal(string $principalUri): string {
		$uri = explode('/', $principalUri);
		return array_pop($uri);
	}
}
