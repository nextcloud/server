<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\Files\Tests\Listener;

use Exception;
use OCP\Constants;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\IRootFolder;
use OCP\Files\ISetupManager;
use OCP\Interaction\Resources\NodeResource;
use OCP\Interaction\RestrictInteractionEvent;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Server;
use PHPUnit\Framework\Attributes\Group;
use Test\TestCase;

#[Group('DB')]
final class RestrictInteractionListenerTest extends TestCase {
	private IUser $user;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();
		$user = Server::get(IUserManager::class)->createUser('user', 'password');
		$this->assertNotFalse($user);
		$this->user = $user;

		Server::get(ISetupManager::class)->setupForUser($user);
	}

	#[\Override]
	protected function tearDown(): void {
		Server::get(ISetupManager::class)->tearDown();

		$this->assertTrue($this->user->delete());

		parent::tearDown();
	}

	public function testNodeResourceShareActionMissingReadPermission(): void {
		$userFolder = Server::get(IRootFolder::class)->getUserFolder($this->user->getUID());

		$fileNode = $userFolder->newFile('foo.txt', 'bar');
		$fileNode->getStorage()->getCache()->update($fileNode->getId(), ['permissions' => Constants::PERMISSION_ALL & ~Constants::PERMISSION_READ]);

		$folderNode = $userFolder->newFolder('foo');
		$folderNode->getStorage()->getCache()->update($folderNode->getId(), ['permissions' => Constants::PERMISSION_ALL & ~Constants::PERMISSION_READ]);

		foreach ([$fileNode, $folderNode] as $node) {
			$event = new RestrictInteractionEvent($this->user->getUID(), $this->user, new NodeResource($node->getId(), $this->user->getUID(), $node), null, null);

			try {
				Server::get(IEventDispatcher::class)->dispatchTyped($event);
				$this->fail('Interaction not restricted.');
			} catch (Exception $e) {
				$this->assertEquals('No read permission on the node.', $e->getMessage());
			}
		}
	}
}
