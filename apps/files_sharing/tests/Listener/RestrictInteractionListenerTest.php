<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\Files_Sharing\Tests\Listener;

use OC\Core\Sharing\Permission\ReshareSharePermissionType;
use OCA\Files\Sharing\Permission\NodeCreateSharePermissionType;
use OCA\Files\Sharing\Permission\NodeDeleteSharePermissionType;
use OCA\Files\Sharing\Permission\NodeReadSharePermissionType;
use OCA\Files\Sharing\Permission\NodeUpdateSharePermissionType;
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
use OCP\Share\IManager;
use OCP\Share\IShare;
use PHPUnit\Framework\Attributes\Group;
use Test\TestCase;

#[Group('DB')]
final class RestrictInteractionListenerTest extends TestCase {
	private IUser $user;
	private ?IUser $recipient = null;

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

		if ($this->recipient !== null) {
			$this->assertTrue($this->recipient->delete());
		}

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

	public function testNodeResourceShareActionSharePermissionOnOtherPath(): void {
		$userManager = Server::get(IUserManager::class);
		$shareManager = Server::get(IManager::class);
		$setupManager = Server::get(ISetupManager::class);

		$recipient = $userManager->createUser('recipient', 'password');
		$this->assertInstanceOf(IUser::class, $recipient);
		$this->recipient = $recipient;

		$userFolder = Server::get(IRootFolder::class)->getUserFolder($this->user->getUID());
		$parentNode = $userFolder->newFolder('parent');
		$childNode = $parentNode->newFolder('child');

		// The recipient reaches the child through both a shareable parent mount and a read-only direct mount.
		$parentShare = $shareManager->newShare()
			->setShareType(IShare::TYPE_USER)
			->setSharedWith($recipient->getUID())
			->setSharedBy($this->user->getUID())
			->setShareOwner($this->user->getUID())
			->setNode($parentNode)
			->setPermissions(Constants::PERMISSION_ALL);
		$parentShare = $shareManager->createShare($parentShare);
		$shareManager->acceptShare($parentShare, $recipient->getUID());

		$childShare = $shareManager->newShare()
			->setShareType(IShare::TYPE_USER)
			->setSharedWith($recipient->getUID())
			->setSharedBy($this->user->getUID())
			->setShareOwner($this->user->getUID())
			->setNode($childNode)
			->setPermissions(Constants::PERMISSION_READ);
		$childShare = $shareManager->createShare($childShare);
		$shareManager->acceptShare($childShare, $recipient->getUID());

		$setupManager->tearDown();
		$setupManager->setupForUser($recipient);

		$recipientFolder = Server::get(IRootFolder::class)->getUserFolder($recipient->getUID());
		$nodes = $recipientFolder->getById($childNode->getId());

		$this->assertCount(2, $nodes);

		$readOnlyNode = null;
		$shareableNode = null;
		foreach ($nodes as $node) {
			if (($node->getPermissions() & Constants::PERMISSION_SHARE) === 0) {
				$readOnlyNode = $node;
			} else {
				$shareableNode = $node;
			}
		}

		$this->assertNotNull($readOnlyNode);
		$this->assertNotNull($shareableNode);
		$this->assertNotSame($readOnlyNode->getPath(), $shareableNode->getPath());
		$this->assertSame(0, $readOnlyNode->getPermissions() & Constants::PERMISSION_SHARE);
		$this->assertSame(Constants::PERMISSION_SHARE, $shareableNode->getPermissions() & Constants::PERMISSION_SHARE);

		$resource = new NodeResource(
			$childNode->getId(),
			$recipient->getUID(),
			$readOnlyNode,
		);

		$this->assertSame(
			Constants::PERMISSION_SHARE,
			$resource->getNodePermissions() & Constants::PERMISSION_SHARE,
		);

		$event = new RestrictInteractionEvent(
			$recipient->getUID(),
			$recipient,
			[$resource],
			new ShareAction(),
			[],
		);

		$this->assertFalse($event->isInteractionRestricted());
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

			$event = new RestrictInteractionEvent($this->user->getUID(), $this->user, [new NodeResource($node->getId(), $this->user->getUID(), $node)], new ShareAction(null, [NodeReadSharePermissionType::class, ReshareSharePermissionType::class, NodeUpdateSharePermissionType::class]), []);
			$this->assertFalse($event->isInteractionRestricted());
		}
	}

	public function testNodeResourceShareActionIncreasePermissionFileDelete(): void {
		$userFolder = Server::get(IRootFolder::class)->getUserFolder($this->user->getUID());

		$node = $userFolder->newFile('foo.txt', 'bar');
		$node->getStorage()->getCache()->update($node->getId(), ['permissions' => Constants::PERMISSION_READ | Constants::PERMISSION_SHARE]);

		$event = new RestrictInteractionEvent($this->user->getUID(), $this->user, [new NodeResource($node->getId(), $this->user->getUID(), $node)], new ShareAction(Constants::PERMISSION_READ | Constants::PERMISSION_SHARE | Constants::PERMISSION_DELETE), []);
		$this->assertEquals('File cannot be shared with delete permission.', $event->isInteractionRestricted());

		$event = new RestrictInteractionEvent($this->user->getUID(), $this->user, [new NodeResource($node->getId(), $this->user->getUID(), $node)], new ShareAction(null, [NodeReadSharePermissionType::class, ReshareSharePermissionType::class, NodeDeleteSharePermissionType::class]), []);
		$this->assertFalse($event->isInteractionRestricted());
	}

	public function testNodeResourceShareActionIncreasePermissionFileCreate(): void {
		$userFolder = Server::get(IRootFolder::class)->getUserFolder($this->user->getUID());

		$node = $userFolder->newFile('foo.txt', 'bar');
		$node->getStorage()->getCache()->update($node->getId(), ['permissions' => Constants::PERMISSION_READ | Constants::PERMISSION_SHARE]);

		$event = new RestrictInteractionEvent($this->user->getUID(), $this->user, [new NodeResource($node->getId(), $this->user->getUID(), $node)], new ShareAction(Constants::PERMISSION_READ | Constants::PERMISSION_SHARE | Constants::PERMISSION_CREATE), []);
		$this->assertEquals('File cannot be shared with create permission.', $event->isInteractionRestricted());

		$event = new RestrictInteractionEvent($this->user->getUID(), $this->user, [new NodeResource($node->getId(), $this->user->getUID(), $node)], new ShareAction(null, [NodeReadSharePermissionType::class, ReshareSharePermissionType::class, NodeCreateSharePermissionType::class]), []);
		$this->assertFalse($event->isInteractionRestricted());
	}

	public function testNodeResourceShareActionFileHasDeletePermission(): void {
		$userFolder = Server::get(IRootFolder::class)->getUserFolder($this->user->getUID());

		$node = $userFolder->newFile('foo.txt', 'bar');
		$node->getStorage()->getCache()->update($node->getId(), ['permissions' => Constants::PERMISSION_ALL]);

		$event = new RestrictInteractionEvent($this->user->getUID(), $this->user, [new NodeResource($node->getId(), $this->user->getUID(), $node)], new ShareAction(Constants::PERMISSION_DELETE), []);
		$this->assertEquals('File cannot be shared with delete permission.', $event->isInteractionRestricted());

		$event = new RestrictInteractionEvent($this->user->getUID(), $this->user, [new NodeResource($node->getId(), $this->user->getUID(), $node)], new ShareAction(null, [NodeDeleteSharePermissionType::class]), []);
		$this->assertFalse($event->isInteractionRestricted());
	}

	public function testNodeResourceShareActionFileHasCreatePermission(): void {
		$userFolder = Server::get(IRootFolder::class)->getUserFolder($this->user->getUID());

		$node = $userFolder->newFile('foo.txt', 'bar');
		$node->getStorage()->getCache()->update($node->getId(), ['permissions' => Constants::PERMISSION_ALL]);

		$event = new RestrictInteractionEvent($this->user->getUID(), $this->user, [new NodeResource($node->getId(), $this->user->getUID(), $node)], new ShareAction(Constants::PERMISSION_CREATE), []);
		$this->assertEquals('File cannot be shared with create permission.', $event->isInteractionRestricted());

		$event = new RestrictInteractionEvent($this->user->getUID(), $this->user, [new NodeResource($node->getId(), $this->user->getUID(), $node)], new ShareAction(null, [NodeCreateSharePermissionType::class]), []);
		$this->assertFalse($event->isInteractionRestricted());
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
			foreach ([
				new ShareAction(Constants::PERMISSION_ALL & ~Constants::PERMISSION_READ),
				new ShareAction(null, [NodeUpdateSharePermissionType::class, NodeCreateSharePermissionType::class, NodeDeleteSharePermissionType::class, ReshareSharePermissionType::class]),
			] as $action) {
				$event = new RestrictInteractionEvent($this->user->getUID(), $this->user, [$resource], $action, [$receiver]);
				$this->assertEquals('File share needs at least read permission.', $event->isInteractionRestricted());
			}
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
				new ShareAction(Constants::PERMISSION_CREATE),
				new ShareAction(null, [NodeCreateSharePermissionType::class]),
				new ShareAction(Constants::PERMISSION_UPDATE),
				new ShareAction(null, [NodeUpdateSharePermissionType::class]),
				new ShareAction(Constants::PERMISSION_DELETE),
				new ShareAction(null, [NodeDeleteSharePermissionType::class]),
			] as $action) {
				$event = new RestrictInteractionEvent($this->user->getUID(), $this->user, [$resource], $action, [$receiver]);
				$this->assertEquals('Public upload is not allowed.', $event->isInteractionRestricted());
			}
		}

		$config->deleteAppValue('core', 'shareapi_allow_public_upload');
	}
}
