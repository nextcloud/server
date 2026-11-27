<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\WorkflowEngine\Tests\Entity;

use OC\Files\Config\UserMountCache;
use OCA\WorkflowEngine\Entity\File;
use OCP\Files\IRootFolder;
use OCP\Files\Mount\IMountManager;
use OCP\Files\Node;
use OCP\Files\Storage\IStorage;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\SystemTag\ISystemTagManager;
use OCP\SystemTag\MapperEvent;
use OCP\WorkflowEngine\IRuleMatcher;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class FileTest extends TestCase {
	private IRootFolder&MockObject $root;
	private File $entity;

	protected function setUp(): void {
		parent::setUp();

		$this->root = $this->createMock(IRootFolder::class);
		$this->entity = new File(
			$this->createMock(IL10N::class),
			$this->createMock(IURLGenerator::class),
			$this->root,
			$this->createMock(IUserSession::class),
			$this->createMock(ISystemTagManager::class),
			$this->createMock(IUserManager::class),
			$this->createMock(UserMountCache::class),
			$this->createMock(IMountManager::class),
		);
	}

	public function testPrepareRuleMatcherUsesNodeOfEachTagAssignEvent(): void {
		$storage = $this->createMock(IStorage::class);
		$node1 = $this->createMock(Node::class);
		$node1->method('getStorage')->willReturn($storage);
		$node1->method('getInternalPath')->willReturn('files/node1.pdf');
		$node2 = $this->createMock(Node::class);
		$node2->method('getStorage')->willReturn($storage);
		$node2->method('getInternalPath')->willReturn('files/node2.pdf');
		$this->root->method('getFirstNodeById')
			->willReturnMap([
				[1, $node1],
				[2, $node2],
			]);

		$subjects = [];
		$ruleMatcher = $this->createMock(IRuleMatcher::class);
		$ruleMatcher->method('setEntitySubject')
			->willReturnCallback(function ($entity, $subject) use (&$subjects): void {
				$subjects[] = $subject;
			});

		$this->entity->prepareRuleMatcher($ruleMatcher, MapperEvent::EVENT_ASSIGN, new MapperEvent(MapperEvent::EVENT_ASSIGN, 'files', '1', [4]));
		$this->entity->prepareRuleMatcher($ruleMatcher, MapperEvent::EVENT_ASSIGN, new MapperEvent(MapperEvent::EVENT_ASSIGN, 'files', '2', [4]));

		$this->assertSame([$node1, $node2], $subjects);
	}
}
