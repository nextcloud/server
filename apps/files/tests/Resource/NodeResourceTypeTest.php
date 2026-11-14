<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\Files\Tests\Resource;

use NCU\Resource\Permission\AccessEntry;
use NCU\Resource\Permission\IPermissionManager;
use NCU\Resource\ResourceReference;
use OCA\Files\Resource\NodeResourceType;
use OCP\Actor\Actor;
use OCP\Actor\Principal;
use OCP\Constants;
use OCP\Files\File;
use OCP\Files\IRootFolder;
use OCP\Files\IUserFolder;
use OCP\Files\Storage\ISharedStorage;
use OCP\Files\Storage\IStorage;
use OCP\L10N\IFactory;
use OCP\Server;
use OCP\Share\IShare;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class NodeResourceTypeTest extends TestCase {
	private IRootFolder&MockObject $rootFolder;
	private IUserFolder&MockObject $userFolder;
	private NodeResourceType $resourceType;
	private Actor $alice;

	protected function setUp(): void {
		parent::setUp();

		$this->userFolder = $this->createMock(IUserFolder::class);
		$this->rootFolder = $this->createMock(IRootFolder::class);
		$this->rootFolder->method('getUserFolder')->with('alice')->willReturn($this->userFolder);
		$this->resourceType = new NodeResourceType($this->createMock(IFactory::class), $this->rootFolder);
		$this->alice = Actor::of(Principal::user('alice'));
	}

	private function createNode(int $permissions, ?bool $shareCanDownload = null): File&MockObject {
		$storage = $this->createMock($shareCanDownload === null ? IStorage::class : ISharedStorage::class);
		$storage->method('instanceOfStorage')->willReturnCallback(static fn (string $class): bool => $storage instanceof $class);
		if ($shareCanDownload !== null) {
			$share = $this->createMock(IShare::class);
			$share->method('canDownload')->willReturn($shareCanDownload);
			$storage->method('getShare')->willReturn($share);
		}

		$node = $this->createMock(File::class);
		$node->method('getPermissions')->willReturn($permissions);
		$node->method('getStorage')->willReturn($storage);

		return $node;
	}

	public function testIsRegistered(): void {
		$resourceType = Server::get(IPermissionManager::class)->getResourceType(NodeResourceType::ID);

		$this->assertInstanceOf(NodeResourceType::class, $resourceType);
		$this->assertSame(
			['core:read', 'core:update', 'core:create', 'core:delete', 'core:share', 'core:download'],
			$resourceType->getPermissionIds(),
		);
	}

	public static function dataPermissions(): array {
		return [
			'all' => [Constants::PERMISSION_ALL, null, ['core:read', 'core:update', 'core:create', 'core:delete', 'core:share', 'core:download']],
			'read only' => [Constants::PERMISSION_READ, null, ['core:read', 'core:download']],
			'file drop' => [Constants::PERMISSION_CREATE, null, ['core:create']],
			'share with download' => [Constants::PERMISSION_READ | Constants::PERMISSION_UPDATE, true, ['core:read', 'core:update', 'core:download']],
			'share without download' => [Constants::PERMISSION_READ | Constants::PERMISSION_SHARE, false, ['core:read', 'core:share']],
		];
	}

	/**
	 * @param list<string> $expected
	 */
	#[DataProvider('dataPermissions')]
	public function testPermissionsOfTheNodeAreGrantedToTheUser(int $permissions, ?bool $shareCanDownload, array $expected): void {
		$this->userFolder->method('getFirstNodeById')->with(42)->willReturn($this->createNode($permissions, $shareCanDownload));

		$this->assertEquals(
			['files:node/42' => [new AccessEntry(AccessEntry::RECIPIENT_USER, 'alice', $expected)]],
			$this->resourceType->getAccess($this->alice, [new ResourceReference(NodeResourceType::ID, '42')]),
		);
	}

	public function testNodesWithoutAccessAreLeftOut(): void {
		$this->userFolder->method('getFirstNodeById')->willReturnMap([
			[1, $this->createNode(Constants::PERMISSION_READ)],
			[2, null],
			[3, $this->createNode(0)],
		]);

		$access = $this->resourceType->getAccess($this->alice, [
			new ResourceReference(NodeResourceType::ID, '1'),
			new ResourceReference(NodeResourceType::ID, '2'),
			new ResourceReference(NodeResourceType::ID, '3'),
			new ResourceReference(NodeResourceType::ID, 'not-a-file-id'),
		]);

		$this->assertSame(['files:node/1'], array_keys($access));
	}

	public function testDelegatedActorGetsTheAccessOfTheUser(): void {
		$this->userFolder->method('getFirstNodeById')->willReturn($this->createNode(Constants::PERMISSION_READ));
		$actor = Actor::of(Principal::system('retention', 'files_versions'))->onBehalfOf(Principal::user('alice'));

		$this->assertArrayHasKey('files:node/1', $this->resourceType->getAccess($actor, [new ResourceReference(NodeResourceType::ID, '1')]));
	}

	public static function dataActorsWithoutAccess(): array {
		return [
			'anonymous' => [Actor::of(Principal::anonymous('token', 'files_sharing:visitor'))],
			'federated user' => [Actor::of(Principal::user('alice', 'cloud.example'))],
			'system' => [Actor::of(Principal::system('scan', 'files'))],
			'bot' => [Actor::of(Principal::bot('summarizer', 'assistant'))],
		];
	}

	#[DataProvider('dataActorsWithoutAccess')]
	public function testOnlyLocalUsersHaveAccess(Actor $actor): void {
		$this->rootFolder->expects($this->never())->method('getUserFolder');

		$this->assertSame([], $this->resourceType->getAccess($actor, [new ResourceReference(NodeResourceType::ID, '1')]));
	}
}
