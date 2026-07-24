<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

use OC\Files\Filesystem;
use OC\User\Database;
use OCA\Files\Sharing\Source\NodeShareSourceType;
use OCA\Sharing\SharingBackend;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\IRootFolder;
use OCP\Files\Node;
use OCP\IDBConnection;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Server;
use OCP\Sharing\ISharingManager;
use OCP\Sharing\ISharingRegistry;
use OCP\Sharing\ShareAccessContext;
use OCP\Sharing\Source\ShareSource;
use PHPUnit\Framework\Attributes\Group;
use Test\TestCase;

#[Group(name: 'DB')]
final class NodeShareSourceTypeTest extends TestCase {
	private IDBConnection $dbConnection;

	private ISharingManager $manager;

	private IUser $user1;

	private Node $node;

	private NodeShareSourceType $sourceType;

	#[\Override]
	public function setUp(): void {
		parent::setUp();

		$this->dbConnection = Server::get(IDBConnection::class);

		$this->manager = Server::get(ISharingManager::class);

		$userManager = Server::get(IUserManager::class);
		$userManager->clearBackends();
		$userManager->registerBackend(new Database());

		$user1 = $userManager->createUser('user1', 'password');
		$this->assertNotFalse($user1);
		$this->user1 = $user1;

		$userFolder = Server::get(IRootFolder::class)->getUserFolder($this->user1->getUID());
		$this->node = $userFolder->newFile('foo.txt', 'bar');

		$this->sourceType = new NodeShareSourceType(Server::get(IEventDispatcher::class), $this->dbConnection, Server::get(IRootFolder::class), Server::get(IURLGenerator::class), $this->manager);
	}

	#[\Override]
	protected function tearDown(): void {
		$this->user1->delete();

		Filesystem::tearDown();

		parent::tearDown();
	}

	public function testValidateSource(): void {
		$this->assertTrue($this->sourceType->validateSource((string)$this->node->getId()));
		$this->assertFalse($this->sourceType->validateSource('-1'));
	}

	public function testGetSourceDisplayName(): void {
		$this->assertEquals('foo.txt', $this->sourceType->getSourceDisplayName((string)$this->node->getId()));
	}

	public function testGetSourceIcon(): void {
		$source = (string)$this->node->getId();

		$icon = $this->sourceType->getSourceIcon($source);

		foreach ([$icon->light, $icon->dark] as $url) {
			$this->assertStringStartsWith('http://localhost/index.php/core/preview?', $url);
			// The order of query parameters in the IURLGenerator is not deterministic, so we have to resort to manually checking the query parameters while ignoring the order.
			$query = parse_url($url, PHP_URL_QUERY);
			$this->assertIsString($query);
			$this->assertEqualsCanonicalizing(['fileId=' . $source, 'x=64', 'y=64'], explode('&', $query));
		}
	}

	public function testDelete(): void {
		$registry = Server::get(ISharingRegistry::class);
		$registry->clear();
		$registry->registerSharingBackend(Server::get(SharingBackend::class));
		$registry->registerSourceType($this->sourceType);

		$accessContext = new ShareAccessContext(currentUser: $this->user1);

		$this->dbConnection->beginTransaction();
		$id = $this->manager->createShare($accessContext);
		$this->manager->addShareSource($accessContext, $id, new ShareSource($this->sourceType::class, (string)$this->node->getId()));
		$this->dbConnection->commit();

		$before = $this->manager->generateTimestamp();
		$this->node->delete();
		$after = $this->manager->generateTimestamp();

		$this->dbConnection->beginTransaction();
		$share = $this->manager->getShare($accessContext, $id);
		$this->assertGreaterThanOrEqual($before, $share->lastUpdated);
		$this->assertLessThanOrEqual($after, $share->lastUpdated);
		$this->assertEquals([], $share->sources);

		$this->manager->deleteShare($accessContext, $id);
		$this->dbConnection->commit();
		$registry->clear();
	}
}
