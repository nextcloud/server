<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\Files_Sharing\Tests\Listener;

use OCP\Constants;
use OCP\Files\IRootFolder;
use OCP\Files\ISetupManager;
use OCP\IConfig;
use OCP\Interaction\Actions\ShareAction;
use OCP\Interaction\Receivers\CircleReceiver;
use OCP\Interaction\Receivers\DeckReceiver;
use OCP\Interaction\Receivers\EmailReceiver;
use OCP\Interaction\Receivers\GroupReceiver;
use OCP\Interaction\Receivers\LinkReceiver;
use OCP\Interaction\Receivers\RemoteGroupReceiver;
use OCP\Interaction\Receivers\RemoteUserReceiver;
use OCP\Interaction\Receivers\RoomReceiver;
use OCP\Interaction\Receivers\UserReceiver;
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

	public function testNodeResourceShareActionMissingSharePermission(): void {
		$userFolder = Server::get(IRootFolder::class)->getUserFolder($this->user->getUID());

		$fileNode = $userFolder->newFile('foo.txt', 'bar');
		$fileNode->getStorage()->getCache()->update($fileNode->getId(), ['permissions' => Constants::PERMISSION_ALL & ~Constants::PERMISSION_SHARE]);
		$fileNode = $userFolder->getFirstNodeById($fileNode->getId());
		$this->assertNotNull($fileNode);

		$folderNode = $userFolder->newFolder('foo');
		$folderNode->getStorage()->getCache()->update($folderNode->getId(), ['permissions' => Constants::PERMISSION_ALL & ~Constants::PERMISSION_SHARE]);
		$folderNode = $userFolder->getFirstNodeById($folderNode->getId());
		$this->assertNotNull($folderNode);

		foreach ([$fileNode, $folderNode] as $node) {
			$event = new RestrictInteractionEvent($this->user->getUID(), $this->user, [new NodeResource($node->getId(), $this->user->getUID(), $node)], new ShareAction(), []);
			$this->assertEquals('You are not allowed to share "' . $node->getName() . '".', $event->isInteractionRestricted());
		}
	}

	public function testNodeResourceShareActionNotHomeFolder(): void {
		$userFolder = Server::get(IRootFolder::class)->getUserFolder($this->user->getUID());

		$event = new RestrictInteractionEvent($this->user->getUID(), $this->user, [new NodeResource($userFolder->getId(), $this->user->getUID(), $userFolder)], new ShareAction(), []);
		$this->assertEquals('You cannot share your home folder.', $event->isInteractionRestricted());
	}

	public function testNodeResourceShareActionIncreasePermission(): void {
		$userFolder = Server::get(IRootFolder::class)->getUserFolder($this->user->getUID());

		$fileNode = $userFolder->newFile('foo.txt', 'bar');
		$fileNode->getStorage()->getCache()->update($fileNode->getId(), ['permissions' => Constants::PERMISSION_READ | Constants::PERMISSION_SHARE]);

		$folderNode = $userFolder->newFolder('foo');
		$folderNode->getStorage()->getCache()->update($folderNode->getId(), ['permissions' => Constants::PERMISSION_READ | Constants::PERMISSION_SHARE]);

		foreach ([$fileNode, $folderNode] as $node) {
			$event = new RestrictInteractionEvent($this->user->getUID(), $this->user, [new NodeResource($node->getId(), $this->user->getUID(), $node)], new ShareAction(Constants::PERMISSION_READ | Constants::PERMISSION_SHARE | Constants::PERMISSION_UPDATE), []);
			$this->assertEquals('You cannot share "/' . $node->getName() . '" with more permission than you have yourself.', $event->isInteractionRestricted());
		}
	}

	public function testNodeResourceShareActionIncreasePermissionFileDelete(): void {
		$userFolder = Server::get(IRootFolder::class)->getUserFolder($this->user->getUID());

		$node = $userFolder->newFile('foo.txt', 'bar');
		$node->getStorage()->getCache()->update($node->getId(), ['permissions' => Constants::PERMISSION_READ | Constants::PERMISSION_SHARE]);

		$event = new RestrictInteractionEvent($this->user->getUID(), $this->user, [new NodeResource($node->getId(), $this->user->getUID(), $node)], new ShareAction(Constants::PERMISSION_READ | Constants::PERMISSION_SHARE | Constants::PERMISSION_DELETE), []);
		$this->assertEquals('File cannot be shared with delete permission.', $event->isInteractionRestricted());
	}

	public function testNodeResourceShareActionIncreasePermissionFileCreate(): void {
		$userFolder = Server::get(IRootFolder::class)->getUserFolder($this->user->getUID());

		$node = $userFolder->newFile('foo.txt', 'bar');
		$node->getStorage()->getCache()->update($node->getId(), ['permissions' => Constants::PERMISSION_READ | Constants::PERMISSION_SHARE]);

		$event = new RestrictInteractionEvent($this->user->getUID(), $this->user, [new NodeResource($node->getId(), $this->user->getUID(), $node)], new ShareAction(Constants::PERMISSION_READ | Constants::PERMISSION_SHARE | Constants::PERMISSION_CREATE), []);
		$this->assertEquals('File cannot be shared with create permission.', $event->isInteractionRestricted());
	}

	public function testNodeResourceShareActionFileHasDeletePermission(): void {
		$userFolder = Server::get(IRootFolder::class)->getUserFolder($this->user->getUID());

		$node = $userFolder->newFile('foo.txt', 'bar');
		$node->getStorage()->getCache()->update($node->getId(), ['permissions' => Constants::PERMISSION_ALL]);

		$event = new RestrictInteractionEvent($this->user->getUID(), $this->user, [new NodeResource($node->getId(), $this->user->getUID(), $node)], new ShareAction(Constants::PERMISSION_DELETE), []);
		$this->assertEquals('File cannot be shared with delete permission.', $event->isInteractionRestricted());
	}

	public function testNodeResourceShareActionFileHasCreatePermission(): void {
		$userFolder = Server::get(IRootFolder::class)->getUserFolder($this->user->getUID());

		$node = $userFolder->newFile('foo.txt', 'bar');
		$node->getStorage()->getCache()->update($node->getId(), ['permissions' => Constants::PERMISSION_ALL]);

		$event = new RestrictInteractionEvent($this->user->getUID(), $this->user, [new NodeResource($node->getId(), $this->user->getUID(), $node)], new ShareAction(Constants::PERMISSION_CREATE), []);
		$this->assertEquals('File cannot be shared with create permission.', $event->isInteractionRestricted());
	}

	/** @psalm-suppress DeprecatedMethod The configs are not migrated to IAppConfig, so using deprecated IConfig is required for now. */
	public function testNodeResourceShareActionNoLinkEmailReceiverMissingReadPermission(): void {
		$config = Server::get(IConfig::class);
		// Defaults to disabled, so we need to enable it to test the RemoteGroupReceiver.
		$config->setAppValue('files_sharing', 'outgoing_server2server_group_share_enabled', 'yes');

		$userFolder = Server::get(IRootFolder::class)->getUserFolder($this->user->getUID());

		$node = $userFolder->newFolder('foo');
		$node->getStorage()->getCache()->update($node->getId(), ['permissions' => Constants::PERMISSION_ALL]);

		$resource = new NodeResource($node->getId(), $this->user->getUID(), $node);

		foreach ([
			new CircleReceiver(''),
			new DeckReceiver(0),
			new GroupReceiver(''),
			new RemoteGroupReceiver(''),
			new RemoteUserReceiver(''),
			new RoomReceiver(''),
			new UserReceiver(''),
		] as $receiver) {
			$event = new RestrictInteractionEvent($this->user->getUID(), $this->user, [$resource], new ShareAction(Constants::PERMISSION_ALL & ~Constants::PERMISSION_READ), [$receiver]);
			$this->assertEquals('File share needs at least read permission.', $event->isInteractionRestricted());
		}

		$config->deleteAppValue('files_sharing', 'outgoing_server2server_group_share_enabled');
	}

	/** @psalm-suppress DeprecatedMethod The configs are not migrated to IAppConfig, so using deprecated IConfig is required for now. */
	public function testNodeResourceShareActionLinkEmailReceiverPublicUploadDisabled(): void {
		$config = Server::get(IConfig::class);
		$config->setAppValue('core', 'shareapi_allow_public_upload', 'no');

		$userFolder = Server::get(IRootFolder::class)->getUserFolder($this->user->getUID());

		$node = $userFolder->newFolder('foo');
		$node->getStorage()->getCache()->update($node->getId(), ['permissions' => Constants::PERMISSION_ALL]);

		$resource = new NodeResource($node->getId(), $this->user->getUID(), $node);

		foreach ([
			new LinkReceiver(),
			new EmailReceiver('test@example.org'),
		] as $receiver) {
			foreach ([
				Constants::PERMISSION_CREATE,
				Constants::PERMISSION_UPDATE,
				Constants::PERMISSION_DELETE,
			] as $permissions) {
				$event = new RestrictInteractionEvent($this->user->getUID(), $this->user, [$resource], new ShareAction($permissions), [$receiver]);
				$this->assertEquals('Public upload is not allowed.', $event->isInteractionRestricted());
			}
		}

		$config->deleteAppValue('core', 'shareapi_allow_public_upload');
	}
}
