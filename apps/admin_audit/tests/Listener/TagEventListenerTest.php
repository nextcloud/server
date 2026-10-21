<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\AdminAudit\Tests\Listener;

use OCA\AdminAudit\IAuditLogger;
use OCA\AdminAudit\Listener\TagEventListener;
use OCP\SystemTag\Events\TagCreatedEvent;
use OCP\SystemTag\Events\TagDeletedEvent;
use OCP\SystemTag\Events\TagUpdatedEvent;
use OCP\SystemTag\ISystemTag;
use OCP\SystemTag\ISystemTagManager;
use OCP\SystemTag\TagAssignedEvent;
use OCP\SystemTag\TagNotFoundException;
use OCP\SystemTag\TagUnassignedEvent;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class TagEventListenerTest extends TestCase {
	private IAuditLogger&MockObject $logger;
	private ISystemTagManager&MockObject $tagManager;
	private TagEventListener $listener;

	protected function setUp(): void {
		parent::setUp();

		$this->logger = $this->createMock(IAuditLogger::class);
		$this->tagManager = $this->createMock(ISystemTagManager::class);
		$this->listener = new TagEventListener($this->logger, $this->tagManager);
	}

	/**
	 * @param array<int, array{visible: bool, assignable: bool}> $tags tag ID => flags; IDs not listed do not exist
	 */
	private function mockTags(array $tags): void {
		$this->tagManager->method('getTagsByIds')
			->willReturnCallback(function (array $tagIds) use ($tags): array {
				$tagId = (int)$tagIds[0];
				if (!isset($tags[$tagId])) {
					throw new TagNotFoundException();
				}
				$tag = $this->createMock(ISystemTag::class);
				$tag->method('isUserVisible')->willReturn($tags[$tagId]['visible']);
				$tag->method('isUserAssignable')->willReturn($tags[$tagId]['assignable']);
				return [(string)$tagId => $tag];
			});
	}

	public function testTagCreated(): void {
		$tag = $this->createMock(ISystemTag::class);
		$tag->method('getName')->willReturn('confidential');
		$tag->method('isUserVisible')->willReturn(false);
		$tag->method('isUserAssignable')->willReturn(false);

		$this->logger->expects($this->once())
			->method('info')
			->with('System tag "confidential" (invisible, system only) created', ['app' => 'admin_audit', 'operation' => 'systemtags.tag.created']);

		$this->listener->handle(new TagCreatedEvent($tag));
	}

	private function createTag(string $id, string $name, bool $visible, bool $assignable): ISystemTag&MockObject {
		$tag = $this->createMock(ISystemTag::class);
		$tag->method('getId')->willReturn($id);
		$tag->method('getName')->willReturn($name);
		$tag->method('isUserVisible')->willReturn($visible);
		$tag->method('isUserAssignable')->willReturn($assignable);
		return $tag;
	}

	public function testTagUpdated(): void {
		$before = $this->createTag('42', 'internal', true, true);
		$after = $this->createTag('42', 'confidential', true, false);

		$this->logger->expects($this->once())
			->method('info')
			->with(
				'System tag 42 ("internal") was updated to "confidential" (visible, system only)',
				$this->callback(fn (array $context): bool => $context['app'] === 'admin_audit' && $context['operation'] === 'systemtags.tag.updated'),
			);

		$this->listener->handle(new TagUpdatedEvent($after, $before));
	}

	public function testTagDeleted(): void {
		$tag = $this->createTag('42', 'confidential', true, false);

		$this->logger->expects($this->once())
			->method('info')
			->with(
				'System tag 42 ("confidential") was deleted',
				$this->callback(fn (array $context): bool => $context['app'] === 'admin_audit' && $context['operation'] === 'systemtags.tag.deleted'),
			);

		$this->listener->handle(new TagDeletedEvent($tag));
	}

	public function testRestrictedTagAssigned(): void {
		$this->mockTags([
			42 => ['visible' => true, 'assignable' => false],
			43 => ['visible' => true, 'assignable' => true],
			44 => ['visible' => false, 'assignable' => false],
		]);

		$this->logger->expects($this->once())
			->method('info')
			->with(
				'Restricted system tags "42, 44" were assigned to 2 files objects "100, 101"',
				$this->callback(fn (array $context): bool => $context['app'] === 'admin_audit' && $context['operation'] === 'systemtags.tag.assigned'),
			);

		$this->listener->handle(new TagAssignedEvent('files', ['100', '101'], [42, 43, 44]));
	}

	public function testUserAssignableTagAssignedIsNotLogged(): void {
		$this->mockTags([43 => ['visible' => true, 'assignable' => true]]);

		$this->logger->expects($this->never())
			->method('info');

		$this->listener->handle(new TagAssignedEvent('files', ['100'], [43]));
	}

	public function testUnknownTagAssignedIsNotLogged(): void {
		$this->mockTags([]);

		$this->logger->expects($this->never())
			->method('info');

		$this->listener->handle(new TagAssignedEvent('files', ['100'], [99]));
	}

	public function testBulkAssignmentCapsObjectIds(): void {
		$this->mockTags([42 => ['visible' => true, 'assignable' => false]]);

		$this->logger->expects($this->once())
			->method('info')
			->with(
				'Restricted system tags "42" were assigned to 12 files objects "1, 2, 3, 4, 5, 6, 7, 8, 9, 10, ..."',
				$this->anything(),
			);

		$this->listener->handle(new TagAssignedEvent('files', array_map('strval', range(1, 12)), [42]));
	}

	public function testRestrictedTagUnassigned(): void {
		$this->mockTags([42 => ['visible' => false, 'assignable' => false]]);

		$this->logger->expects($this->once())
			->method('info')
			->with(
				'Restricted system tags "42" were unassigned from 1 files objects "100"',
				$this->callback(fn (array $context): bool => $context['app'] === 'admin_audit' && $context['operation'] === 'systemtags.tag.unassigned'),
			);

		$this->listener->handle(new TagUnassignedEvent('files', ['100'], [42]));
	}

	public function testUserAssignableTagUnassignedIsNotLogged(): void {
		$this->mockTags([43 => ['visible' => true, 'assignable' => true]]);

		$this->logger->expects($this->never())
			->method('info');

		$this->listener->handle(new TagUnassignedEvent('files', ['100'], [43]));
	}
}
