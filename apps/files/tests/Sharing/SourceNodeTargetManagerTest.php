<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\Files\Tests\Sharing;

use NCU\Sharing\ShareUser;
use OCA\Files\Sharing\SourceNodeTargetManager;
use OCA\Files_Sharing\AppInfo\Application;
use OCP\Config\IUserConfig;
use OCP\Files\IRootFolder;
use OCP\Files\Node;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IUser;
use OCP\Server;
use Override;
use PHPUnit\Framework\Attributes\Group;
use Test\TestCase;
use Test\Traits\UserTrait;

#[Group('DB')]
final class SourceNodeTargetManagerTest extends TestCase {
	use UserTrait;

	private IUser $owner;

	private IUser $user;

	private Node $node;

	private SourceNodeTargetManager $manager;

	#[Override]
	public function setUp(): void {
		parent::setUp();

		$this->owner = $this->createUser('owner', 'password');
		$this->user = $this->createUser('user', 'password');

		$this->node = Server::get(IRootFolder::class)->getUserFolder($this->owner->getUID())->newFile('foo.txt');

		$this->manager = Server::get(SourceNodeTargetManager::class);
	}

	#[Override]
	public function tearDown(): void {
		$qb = Server::get(IDBConnection::class)->getQueryBuilder();
		$qb
			->delete('sharing_source_node_target')
			->executeStatement();

		parent::tearDown();
	}

	public function testCreateDefaultTarget(): void {
		$this->assertEquals('/foo.txt', $this->manager->createDefaultTarget($this->user->getUID(), new ShareUser($this->owner->getUID(), null), $this->node->getId()));
	}

	public function testCreateDefaultSystem(): void {
		$config = Server::get(IConfig::class);

		$config->setSystemValue('share_folder', '/system');
		$this->assertEquals('/system/foo.txt', $this->manager->createDefaultTarget($this->user->getUID(), new ShareUser($this->owner->getUID(), null), $this->node->getId()));

		$config->deleteSystemValue('share_folder');
	}

	public function testCreateDefaultUser(): void {
		$userConfig = Server::get(IUserConfig::class);

		$userConfig->setValueString($this->user->getUID(), Application::APP_ID, 'share_folder', '/user');
		$this->assertEquals('/user/foo.txt', $this->manager->createDefaultTarget($this->user->getUID(), new ShareUser($this->owner->getUID(), null), $this->node->getId()));

		$userConfig->deleteUserConfig($this->user->getUID(), Application::APP_ID, 'share_folder');
	}

	public function testCreateDefaultUserNotAllowed(): void {
		$config = Server::get(IConfig::class);
		$userConfig = Server::get(IUserConfig::class);

		$config->setSystemValue('sharing.allow_custom_share_folder', false);
		$userConfig->setValueString($this->user->getUID(), Application::APP_ID, 'share_folder', '/user');
		$this->assertEquals('/foo.txt', $this->manager->createDefaultTarget($this->user->getUID(), new ShareUser($this->owner->getUID(), null), $this->node->getId()));

		$config->deleteSystemValue('sharing.allow_custom_share_folder');
		$userConfig->deleteUserConfig($this->user->getUID(), Application::APP_ID, 'share_folder');
	}

	public function testGetTarget(): void {
		$this->assertEquals('/foo.txt', $this->manager->getTarget($this->user->getUID(), new ShareUser($this->owner->getUID(), null), $this->node->getId()));
		$this->assertEquals('/foo.txt', $this->manager->getTarget($this->user->getUID(), new ShareUser($this->owner->getUID(), null), $this->node->getId()));
	}

	public function testSetTarget(): void {
		$this->assertNull($this->invokePrivate($this->manager, 'getTargetInternal', [$this->user->getUID(), new ShareUser($this->owner->getUID(), null), $this->node->getId()]));

		$this->manager->setTarget($this->user->getUID(), new ShareUser($this->owner->getUID(), null), $this->node->getId(), '/bar.txt');
		$this->assertEquals('/bar.txt', $this->manager->getTarget($this->user->getUID(), new ShareUser($this->owner->getUID(), null), $this->node->getId()));

		$this->manager->setTarget($this->user->getUID(), new ShareUser($this->owner->getUID(), null), $this->node->getId(), '/baz.txt');
		$this->assertEquals('/baz.txt', $this->manager->getTarget($this->user->getUID(), new ShareUser($this->owner->getUID(), null), $this->node->getId()));
	}
}
