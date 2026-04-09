<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Tests\unit\Comments;

use OCA\DAV\Comments\EntityCollection as EntityCollectionImplemantation;
use OCA\DAV\Comments\EntityTypeCollection;
use OCP\Comments\ICommentsManager;
use OCP\IUserManager;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class EntityTypeCollectionTest extends \Test\TestCase {
	protected ICommentsManager&MockObject $commentsManager;
	protected IUserManager&MockObject $userManager;
	protected LoggerInterface&MockObject $logger;
	protected IUserSession&MockObject $userSession;
	protected EntityTypeCollection $collection;

	protected $childMap = [];

	protected function setUp(): void {
		parent::setUp();

		$this->commentsManager = $this->createMock(ICommentsManager::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->logger = $this->createMock(LoggerInterface::class);

		$this->collection = new EntityTypeCollection(
			'files',
			$this->commentsManager,
			$this->userManager,
			$this->userSession,
			$this->logger,
			function ($child) {
				return !empty($this->childMap[$child]);
			}
		);
	}

	public function testChildExistsYes(): void {
		$this->childMap[17] = true;
		$this->assertTrue($this->collection->childExists('17'));
	}

	public function testChildExistsNo(): void {
		$this->assertFalse($this->collection->childExists('17'));
	}

	public function testGetChild(): void {
		$this->childMap[17] = true;

		$ec = $this->collection->getChild('17');
		$this->assertInstanceOf(EntityCollectionImplemantation::class, $ec);
	}


	public function testGetChildException(): void {
		$this->expectException(\Sabre\DAV\Exception\NotFound::class);

		$this->collection->getChild('17');
	}


	public function testGetChildren(): void {
		$this->expectException(\Sabre\DAV\Exception\MethodNotAllowed::class);

		$this->collection->getChildren();
	}
}
