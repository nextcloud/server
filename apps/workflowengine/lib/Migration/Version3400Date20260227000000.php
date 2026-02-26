<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH
 * SPDX-FileContributor: Carl Schwan
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\WorkflowEngine\Migration;

use OC\SystemTag\Events\SingleTagAssignedEvent;
use OCP\Files\Events\Node\NodeCopiedEvent;
use OCP\Files\Events\Node\NodeCreatedEvent;
use OCP\Files\Events\Node\NodeDeletedEvent;
use OCP\Files\Events\Node\NodeRenamedEvent;
use OCP\Files\Events\Node\NodeTouchedEvent;
use OCP\Files\Events\Node\NodeUpdatedEvent;
use OCP\IDBConnection;
use OCP\Migration\Attributes\ModifyColumn;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
use Override;

#[ModifyColumn(table: 'flow_operations', name: 'events', description: 'Use new event names')]
class Version3400Date20260227000000 extends SimpleMigrationStep {
	public function __construct(
		private readonly IDBConnection $connection,
	) {
	}

	#[Override]
	public function postSchemaChange(IOutput $output, \Closure $schemaClosure, array $options) {
		$qb = $this->connection->getQueryBuilder();
		$lastId = null;
		while (true) {
			$qb->select('*')
				->from('flow_operations');
			if ($lastId !== null) {
				$qb->andWhere($qb->expr()->gt('id', $qb->createNamedParameter($lastId)));
			}
			$qb->setMaxResults(1000);
			$newMapping = [];
			$result = $qb->executeQuery();
			while ($row = $result->fetchAssociative()) {
				$events = json_decode($row['events'], true);
				$newEvents = array_map(function (string $eventName): string {
					return match ($eventName) {
						'\OCP\Files::postCreate' => NodeCreatedEvent::class,
						'\OCP\Files::postUpdate' => NodeUpdatedEvent::class,
						'\OCP\Files::postRename' => NodeRenamedEvent::class,
						'\OCP\Files::postDelete' => NodeDeletedEvent::class,
						'\OCP\Files::postTouch' => NodeTouchedEvent::class,
						'\OCP\Files::postCopy' => NodeCopiedEvent::class,
						'OCP\SystemTag\ISystemTagObjectMapper::assignTags' => SingleTagAssignedEvent::class,
					};
				}, $events);

				if ($newEvents !== $events) {
					$newMapping[$row['id']] = json_encode($newEvents);
				}
			}
			$result->closeCursor();

			try {
				if ($newMapping !== []) {
					$this->connection->beginTransaction();
				}
				foreach ($newMapping as $id => $events) {
					$update = $this->connection->getQueryBuilder();
					$update->update('flow_operations')
						->set('events', $update->createNamedParameter($events))
						->where($qb->expr()->eq('id', $update->createNamedParameter($id)))
						->executeStatement();
				}
				if ($newMapping !== []) {
					$this->connection->commit();
				}
			} catch (\Exception $e) {
				$this->connection->rollback();
				throw $e;
			}

			if ($row !== false) {
				$lastId = $row['id'];
			} else {
				break;
			}
		}

		return $schemaClosure();
	}
}
