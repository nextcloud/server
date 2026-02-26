<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH
 * SPDX-FileContributor: Carl Schwan
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\WorkflowEngine\Tests\Migration;

use OC\SystemTag\Events\SingleTagAssignedEvent;
use OCA\WorkflowEngine\Entity\File;
use OCA\WorkflowEngine\Migration\Version3400Date20260227000000;
use OCP\DB\ISchemaWrapper;
use OCP\Files\Events\Node\NodeCopiedEvent;
use OCP\Files\Events\Node\NodeCreatedEvent;
use OCP\Files\Events\Node\NodeDeletedEvent;
use OCP\Files\Events\Node\NodeRenamedEvent;
use OCP\Files\Events\Node\NodeTouchedEvent;
use OCP\Files\Events\Node\NodeUpdatedEvent;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Server;
use PHPUnit\Framework\Attributes\Group;
use Test\TestCase;

#[Group(name: 'DB')]
class Version3400Date20260227000000Test extends TestCase {
	public function tearDown(): void {
		parent::tearDown();

		$db = Server::get(IDBConnection::class);
		$qb = $db->getQueryBuilder();
		$qb->delete('flow_operations')
			->executeStatement();
	}

	public function testMigration(): void {
		$db = Server::get(IDBConnection::class);
		foreach (['\OCP\Files::postCreate', '\OCP\Files::postUpdate', '\OCP\Files::postRename', '\OCP\Files::postDelete',
			'\OCP\Files::postTouch', '\OCP\Files::postCopy', 'OCP\SystemTag\ISystemTagObjectMapper::assignTags'] as $legacyEventName) {
			$qb = $db->getQueryBuilder();
			$qb->insert('flow_operations')
				->values([
					'class' => $qb->createNamedParameter('OCA\FlowNotifications\Flow\Operation'),
					'name' => $qb->createNamedParameter(''),
					'operation' => $qb->createNamedParameter('{"inscription":"Test"}'),
					'entity' => $qb->createNamedParameter(File::class),
					'events' => $qb->createNamedParameter(json_encode([$legacyEventName])),
				])->executeStatement();
		}

		$migration = Server::get(Version3400Date20260227000000::class);
		$migration->postSchemaChange(
			$this->createMock(IOutput::class),
			fn () => $this->createMock(ISchemaWrapper::class),
			[]
		);

		$qb = $db->getQueryBuilder();
		$events = $qb->select('events')
			->from('flow_operations')
			->executeQuery()
			->fetchFirstColumn();
		foreach ($events as $eventGroup) {
			$events = json_decode($eventGroup, true);
			foreach ($events as $event) {
				$this->assertTrue(in_array($event, [
					NodeCreatedEvent::class,
					NodeUpdatedEvent::class,
					NodeDeletedEvent::class,
					NodeRenamedEvent::class,
					NodeTouchedEvent::class,
					NodeCopiedEvent::class,
					SingleTagAssignedEvent::class,
				]));
			}
		}
	}
}
