<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);


use OC\User\Database;
use OCA\Sharing\SourceTypes\NodeShareSourceType;
use OCP\Files\IRootFolder;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Server;
use Test\TestCase;

/**
 * @group DB
 */
class NodeShareSourceTypeTest extends TestCase {
	private IUser $user1;

	private NodeShareSourceType $sourceType;

	public function setUp(): void {
		parent::setUp();

		$userManager = Server::get(IUserManager::class);
		$userManager->clearBackends();
		$userManager->registerBackend(new Database());

		$this->user1 = $userManager->createUser('user1', 'password');

		$this->sourceType = new NodeShareSourceType();
	}

	protected function tearDown(): void {
		$this->user1->delete();

		parent::tearDown();
	}

	public function testValidateSource(): void {
		$userFolder = Server::get(IRootFolder::class)->getUserFolder($this->user1->getUID());
		$node = $userFolder->newFile('foo.txt', 'bar');
		$source = (string)$node->getId();

		$this->assertTrue($this->sourceType->validateSource($this->user1, $source));

		$node->delete();

		$this->assertFalse($this->sourceType->validateSource($this->user1, $source));
	}
}
