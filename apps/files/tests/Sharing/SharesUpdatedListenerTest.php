<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\Files\Tests\Sharing;

use NCU\Sharing\ISharingManager;
use NCU\Sharing\Permission\SharePermission;
use NCU\Sharing\Recipient\ShareRecipient;
use NCU\Sharing\ShareAccessContext;
use NCU\Sharing\ShareState;
use NCU\Sharing\ShareUser;
use NCU\Sharing\Source\ShareSource;
use OC\Core\Sharing\Recipient\UserShareRecipientType;
use OCA\Files\Sharing\Permission\NodeReadSharePermissionType;
use OCA\Files\Sharing\Source\NodeShareSourceType;
use OCA\Files\Sharing\SourceNodeTargetManager;
use OCP\Files\IRootFolder;
use OCP\Files\Node;
use OCP\IDBConnection;
use OCP\IUser;
use OCP\Server;
use Override;
use PHPUnit\Framework\Attributes\Group;
use Test\TestCase;
use Test\Traits\UserTrait;

#[Group('DB')]
final class SharesUpdatedListenerTest extends TestCase {
	use UserTrait;

	private IUser $owner;

	private IUser $user;

	private Node $node;

	private IDBConnection $dbConnection;

	private ISharingManager $sharingManager;

	private SourceNodeTargetManager $sourceNodeTargetManager;

	#[Override]
	public function setUp(): void {
		parent::setUp();

		$this->owner = $this->createUser('owner', 'password');
		$this->user = $this->createUser('user', 'password');

		$this->node = Server::get(IRootFolder::class)->getUserFolder($this->owner->getUID())->newFile('foo.txt');

		$this->dbConnection = Server::get(IDBConnection::class);
		$this->sharingManager = Server::get(ISharingManager::class);
		$this->sourceNodeTargetManager = Server::get(SourceNodeTargetManager::class);
	}

	#[Override]
	public function tearDown(): void {
		$qb = Server::get(IDBConnection::class)->getQueryBuilder();
		$qb
			->delete('sharing_source_node_target')
			->executeStatement();

		$accessContext = new ShareAccessContext(overrideChecks: true);
		$this->dbConnection->beginTransaction();
		foreach ($this->sharingManager->getShares($accessContext, null, null, null, null, null, null) as $share) {
			$this->sharingManager->deleteShare($accessContext, $share);
		}

		$this->dbConnection->commit();

		parent::tearDown();
	}

	private function getTargetWithoutDefault(): ?string {
		/** @psalm-suppress MixedReturnStatement */
		return$this->invokePrivate($this->sourceNodeTargetManager, 'getTargetInternal', [$this->user->getUID(), new ShareUser($this->owner->getUID(), null), $this->node->getId()]);
	}

	public function test(): void {
		$accessContext = new ShareAccessContext($this->owner);

		$this->dbConnection->beginTransaction();

		$share = $this->sharingManager->createShare($accessContext);
		$this->assertNull($this->getTargetWithoutDefault());

		$share = $this->sharingManager->addShareSource($accessContext, $share, new ShareSource(NodeShareSourceType::class, (string)$this->node->getId()));
		$this->assertNull($this->getTargetWithoutDefault());

		$share = $this->sharingManager->addShareRecipient($accessContext, $share, new ShareRecipient(UserShareRecipientType::class, $this->user->getUID(), null));
		$this->assertNull($this->getTargetWithoutDefault());

		$share = $this->sharingManager->updateSharePermission($accessContext, $share, new SharePermission(NodeReadSharePermissionType::class, true));
		$this->assertNull($this->getTargetWithoutDefault());

		$this->sharingManager->updateShareState($accessContext, $share, ShareState::Active);
		$this->assertEquals('/foo.txt', $this->getTargetWithoutDefault());

		$this->dbConnection->commit();
	}
}
