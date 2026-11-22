<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH
 * SPDX-FileContributor: Carl Schwan
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\AdminAudit\Listener;

use OCA\AdminAudit\Actions\Action;
use OCA\AdminAudit\IAuditLogger;
use OCA\AdminAudit\Operation;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\SystemTag\Events\TagCreatedEvent;
use OCP\SystemTag\Events\TagDeletedEvent;
use OCP\SystemTag\Events\TagUpdatedEvent;
use OCP\SystemTag\ISystemTag;
use OCP\SystemTag\ISystemTagManager;
use OCP\SystemTag\TagAssignedEvent;
use OCP\SystemTag\TagNotFoundException;
use OCP\SystemTag\TagUnassignedEvent;
use Override;

/**
 * @template-implements IEventListener<TagCreatedEvent|TagUpdatedEvent|TagDeletedEvent|TagAssignedEvent|TagUnassignedEvent>
 */
class TagEventListener extends Action implements IEventListener {
	private const MAX_LOGGED_OBJECT_IDS = 10;

	public function __construct(
		IAuditLogger $logger,
		private ISystemTagManager $tagManager,
	) {
		parent::__construct($logger);
	}

	#[Override]
	public function handle(Event $event): void {
		if ($event instanceof TagCreatedEvent) {
			$this->tagCreated($event);
		} elseif ($event instanceof TagUpdatedEvent) {
			$this->tagUpdated($event);
		} elseif ($event instanceof TagDeletedEvent) {
			$this->tagDeleted($event);
		} elseif ($event instanceof TagAssignedEvent) {
			$this->tagAssigned($event);
		} elseif ($event instanceof TagUnassignedEvent) {
			$this->tagUnassigned($event);
		}
	}

	private function tagCreated(TagCreatedEvent $event): void {
		$tag = $event->getTag();

		$this->log(Operation::SystemTagCreated, 'System tag "%s" (%s, %s) created',
			[
				'name' => $tag->getName(),
				'visibility' => $this->formatVisibility($tag),
				'assignable' => $this->formatAssignable($tag),
			],
			['name', 'visibility', 'assignable']
		);
	}

	private function tagUpdated(TagUpdatedEvent $event): void {
		$tag = $event->getTag();

		$this->log(Operation::SystemTagUpdated, 'System tag %s ("%s") was updated to "%s" (%s, %s)',
			[
				'tagId' => $tag->getId(),
				'previousName' => $event->getTagBefore()->getName(),
				'name' => $tag->getName(),
				'visibility' => $this->formatVisibility($tag),
				'assignable' => $this->formatAssignable($tag),
			],
			['tagId', 'previousName', 'name', 'visibility', 'assignable']
		);
	}

	private function tagDeleted(TagDeletedEvent $event): void {
		$tag = $event->getTag();

		$this->log(Operation::SystemTagDeleted, 'System tag %s ("%s") was deleted',
			[
				'tagId' => $tag->getId(),
				'name' => $tag->getName(),
			],
			['tagId', 'name']
		);
	}

	private function tagAssigned(TagAssignedEvent $event): void {
		$this->logAssignment(Operation::SystemTagAssigned, 'Restricted system tags "%s" were assigned to %s %s objects "%s"', $event->getTags(), $event->getObjectType(), $event->getObjectIds());
	}

	private function tagUnassigned(TagUnassignedEvent $event): void {
		$this->logAssignment(Operation::SystemTagUnassigned, 'Restricted system tags "%s" were unassigned from %s %s objects "%s"', $event->getTags(), $event->getObjectType(), $event->getObjectIds());
	}

	/**
	 * Only restricted and invisible tags are logged, as those are the ones access
	 * control and retention rules rely on. Object IDs are capped to keep bulk
	 * assignments to a single short entry.
	 *
	 * @param list<int|string> $tagIds
	 * @param list<string> $objectIds
	 */
	private function logAssignment(Operation $operation, string $text, array $tagIds, string $objectType, array $objectIds): void {
		$restrictedTagIds = $this->getRestrictedTagIds($tagIds);
		if ($restrictedTagIds === [] || $objectIds === []) {
			return;
		}

		$loggedObjectIds = implode(', ', array_slice($objectIds, 0, self::MAX_LOGGED_OBJECT_IDS));
		if (count($objectIds) > self::MAX_LOGGED_OBJECT_IDS) {
			$loggedObjectIds .= ', ...';
		}

		$this->log($operation, $text,
			[
				'tagIds' => implode(', ', $restrictedTagIds),
				'objectCount' => count($objectIds),
				'objectType' => $objectType,
				'objectIds' => $loggedObjectIds,
			],
			['tagIds', 'objectCount', 'objectType', 'objectIds']
		);
	}

	/**
	 * @param list<int|string> $tagIds
	 * @return list<string>
	 */
	private function getRestrictedTagIds(array $tagIds): array {
		$restrictedTagIds = [];
		foreach ($tagIds as $tagId) {
			try {
				$tag = current($this->tagManager->getTagsByIds([$tagId]));
			} catch (TagNotFoundException|\InvalidArgumentException) {
				continue;
			}
			if ($tag !== false && (!$tag->isUserVisible() || !$tag->isUserAssignable())) {
				$restrictedTagIds[] = (string)$tagId;
			}
		}
		return $restrictedTagIds;
	}

	private function formatVisibility(ISystemTag $tag): string {
		return $tag->isUserVisible() ? 'visible' : 'invisible';
	}

	private function formatAssignable(ISystemTag $tag): string {
		return $tag->isUserAssignable() ? 'user assignable' : 'system only';
	}
}
