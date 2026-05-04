<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);


use OC\Files\Filesystem;
use OC\User\Database;
use OCA\Files\Sharing\Source\NodeShareSourceType;
use OCP\Files\IRootFolder;
use OCP\IL10N;
use OCP\IUser;
use OCP\IUserManager;
use OCP\L10N\IFactory;
use OCP\Server;
use PHPUnit\Framework\Attributes\Group;
use Test\TestCase;

#[Group(name: 'DB')]
final class NodeShareSourceTypeTest extends TestCase {
	private IUser $user1;

	private NodeShareSourceType $sourceType;

	#[\Override]
	public function setUp(): void {
		parent::setUp();

		$userManager = Server::get(IUserManager::class);
		$userManager->clearBackends();
		$userManager->registerBackend(new Database());

		$user1 = $userManager->createUser('user1', 'password');
		$this->assertNotFalse($user1);
		$this->user1 = $user1;

		$this->sourceType = new NodeShareSourceType();
	}

	#[\Override]
	protected function tearDown(): void {
		$this->user1->delete();

		Filesystem::tearDown();

		parent::tearDown();
	}

	public function testGetDisplayName(): void {
		$this->overwriteService(IL10N::class, Server::get(IFactory::class)->get(''));

		$this->assertEquals('File or folder', $this->sourceType->getDisplayName());
	}

	public function testValidateSource(): void {
		$userFolder = Server::get(IRootFolder::class)->getUserFolder($this->user1->getUID());
		$node = $userFolder->newFile('foo.txt', 'bar');
		$source = (string)$node->getId();

		$this->assertTrue($this->sourceType->validateSource($this->user1, $source));

		$node->delete();

		$this->assertFalse($this->sourceType->validateSource($this->user1, $source));
	}

	public function testGetSourceDisplayName(): void {
		$userFolder = Server::get(IRootFolder::class)->getUserFolder($this->user1->getUID());
		$node = $userFolder->newFile('foo.txt', 'bar');
		$source = (string)$node->getId();

		$this->assertEquals('foo.txt', $this->sourceType->getSourceDisplayName($source));
	}
}
