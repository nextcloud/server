<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\unit\Files\NodeAttributes;

use NCU\Files\NodeAttributes\NodeAttributeContext;
use NCU\Files\NodeAttributes\NodeAttributeList;
use OC\Share20\Exception\BackendError;
use OCA\DAV\Connector\Sabre\SharesPlugin;
use OCA\DAV\Files\NodeAttributes\SharesProvider;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\Node;
use OCP\IUser;
use OCP\Share\IManager;
use OCP\Share\IShare;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class SharesProviderTest extends TestCase {
	private const ATTRIBUTES = [SharesPlugin::SHARETYPES_PROPERTYNAME, SharesPlugin::SHAREES_PROPERTYNAME];

	private IManager&MockObject $shareManager;
	private SharesProvider $provider;
	private NodeAttributeContext $context;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->shareManager = $this->createMock(IManager::class);
		$this->provider = new SharesProvider($this->shareManager);

		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('alice');
		$this->context = NodeAttributeContext::forUser($user);
	}

	/**
	 * @param class-string<File|Folder> $class
	 */
	private function node(string $class, int $id, string $path): File|Folder {
		$node = $this->createMock($class);
		$node->method('getId')->willReturn($id);
		$node->method('getPath')->willReturn($path);
		return $node;
	}

	private function share(int $type, ?string $with = null, ?string $displayName = null): IShare&MockObject {
		$share = $this->createMock(IShare::class);
		$share->method('getShareType')->willReturn($type);
		$share->method('getSharedWith')->willReturn($with);
		$share->method('getSharedWithDisplayName')->willReturn($displayName);
		return $share;
	}

	/**
	 * @return array{list<int>, list<array<string, int|string|null>>}
	 */
	private function getValues(Node $node): array {
		$values = $this->provider->getAttributes($this->context, $node, self::ATTRIBUTES);
		$shareTypes = $values[SharesPlugin::SHARETYPES_PROPERTYNAME];
		$sharees = $values[SharesPlugin::SHAREES_PROPERTYNAME];
		self::assertInstanceOf(NodeAttributeList::class, $shareTypes);
		self::assertInstanceOf(NodeAttributeList::class, $sharees);
		self::assertSame('share-type', $shareTypes->getChildName());
		self::assertSame('sharee', $sharees->getChildName());
		return [$shareTypes->getValues(), $sharees->getValues()];
	}

	public static function dataShareTypes(): array {
		return [
			[[]],
			[[IShare::TYPE_USER]],
			[[IShare::TYPE_GROUP]],
			[[IShare::TYPE_LINK]],
			[[IShare::TYPE_REMOTE]],
			[[IShare::TYPE_ROOM]],
			[[IShare::TYPE_DECK]],
			[[IShare::TYPE_USER, IShare::TYPE_GROUP, IShare::TYPE_LINK]],
			[[IShare::TYPE_USER, IShare::TYPE_REMOTE]],
		];
	}

	/**
	 * @param list<int> $shareTypes
	 */
	#[DataProvider('dataShareTypes')]
	public function testOutgoingSharesOfANode(array $shareTypes): void {
		$node = $this->node(File::class, 11, '/alice/files/report.md');
		$this->shareManager->method('getSharesBy')
			->willReturnCallback(fn (string $userId, int $type, Node $sharedNode, bool $reshares, int $limit): array => $userId === 'alice' && $sharedNode === $node && !$reshares && $limit === -1 && in_array($type, $shareTypes, true)
				? [$this->share($type)]
				: []);
		$this->shareManager->method('getSharedWith')->willReturn([]);

		[$types] = $this->getValues($node);
		self::assertSame($shareTypes, $types);
	}

	public function testReceivedSharesAreIncludedForANode(): void {
		$node = $this->node(Folder::class, 12, '/alice/files/Shared');
		$this->shareManager->method('getSharesBy')
			->willReturnCallback(fn (string $userId, int $type): array => $type === IShare::TYPE_LINK ? [$this->share(IShare::TYPE_LINK)] : []);
		$this->shareManager->method('getSharedWith')
			->willReturnCallback(fn (string $userId, int $type, ?Node $sharedNode, int $limit): array => $userId === 'alice' && $sharedNode === $node && $limit === -1 && $type === IShare::TYPE_GROUP
				? [$this->share(IShare::TYPE_GROUP, 'staff', 'Staff')]
				: []);

		[$types, $sharees] = $this->getValues($node);
		self::assertSame([IShare::TYPE_GROUP, IShare::TYPE_LINK], $types);
		self::assertSame([
			['id' => 'staff', 'display-name' => 'Staff', 'type' => IShare::TYPE_GROUP],
			['id' => null, 'display-name' => null, 'type' => IShare::TYPE_LINK],
		], $sharees);
	}

	public function testUnavailableShareProviderIsSkipped(): void {
		$node = $this->node(File::class, 11, '/alice/files/report.md');
		$this->shareManager->method('getSharesBy')
			->willReturnCallback(fn (string $userId, int $type): array => $type === IShare::TYPE_USER ? [$this->share(IShare::TYPE_USER, 'bob', 'Bob')] : []);
		$this->shareManager->method('getSharedWith')
			->willReturnCallback(static fn (string $userId, int $type): array => $type === IShare::TYPE_ROOM ? throw new BackendError() : []);

		[$types] = $this->getValues($node);
		self::assertSame([IShare::TYPE_USER], $types);
	}

	public function testShareTypesAreUnique(): void {
		$node = $this->node(File::class, 11, '/alice/files/report.md');
		$this->shareManager->method('getSharesBy')
			->willReturnCallback(fn (string $userId, int $type): array => $type === IShare::TYPE_USER
				? [$this->share(IShare::TYPE_USER, 'bob', 'Bob'), $this->share(IShare::TYPE_USER, 'carol', 'Carol')]
				: []);
		$this->shareManager->method('getSharedWith')->willReturn([]);

		[$types, $sharees] = $this->getValues($node);
		self::assertSame([IShare::TYPE_USER], $types);
		self::assertSame(['bob', 'carol'], array_column($sharees, 'id'));
	}

	public function testFolderListingLoadsTheChildrenAtOnce(): void {
		$folder = $this->node(Folder::class, 20, '/alice/files/Projects');
		$shared = $this->node(File::class, 21, '/alice/files/Projects/plan.md');
		$notShared = $this->node(File::class, 22, '/alice/files/Projects/notes.md');

		$this->shareManager->expects(self::once())
			->method('getSharesInFolder')
			->with('alice', $folder, true)
			->willReturn([21 => [$this->share(IShare::TYPE_LINK)]]);
		// The folder itself is looked up on its own, its children are not
		$this->shareManager->expects(self::exactly(8))
			->method('getSharesBy')
			->with('alice', self::anything(), $folder, false, -1)
			->willReturn([]);
		$this->shareManager->expects(self::exactly(8))
			->method('getSharedWith')
			->with('alice', self::anything(), $folder, -1)
			->willReturn([]);

		$this->provider->preloadListing($this->context, $folder, [$shared, $notShared], self::ATTRIBUTES);

		self::assertSame([], $this->getValues($folder)[0]);
		self::assertSame([IShare::TYPE_LINK], $this->getValues($shared)[0]);
		self::assertSame([], $this->getValues($notShared)[0]);
	}

	public function testAFolderAndItsChildOutsideOfAListingGetTheirOwnShares(): void {
		// Search results can hold a folder and its children, they are not a listing
		$folder = $this->node(Folder::class, 40, '/alice/files/X');
		$received = $this->node(Folder::class, 41, '/alice/files/X/Y');

		$this->shareManager->expects(self::never())->method('getSharesInFolder');
		$this->shareManager->method('getSharesBy')->willReturn([]);
		$this->shareManager->method('getSharedWith')
			->willReturnCallback(fn (string $userId, int $type, Node $node): array => $node === $received && $type === IShare::TYPE_USER
				? [$this->share(IShare::TYPE_USER, 'alice', 'Alice')]
				: []);

		$this->provider->preload($this->context, [$folder, $received], self::ATTRIBUTES);

		self::assertSame([], $this->getValues($folder)[0]);
		self::assertSame([IShare::TYPE_USER], $this->getValues($received)[0]);
	}

	public function testPreloadingTwiceOnlyLoadsNewNodes(): void {
		$this->shareManager->expects(self::exactly(16))
			->method('getSharesBy')
			->willReturn([]);
		$this->shareManager->method('getSharedWith')->willReturn([]);

		$this->provider->preload($this->context, [$this->node(File::class, 1, '/alice/files/a')], self::ATTRIBUTES);
		$this->provider->preload($this->context, [$this->node(File::class, 1, '/alice/files/a')], self::ATTRIBUTES);
		$this->provider->preload($this->context, [$this->node(File::class, 2, '/alice/files/b')], self::ATTRIBUTES);
	}

	public function testNothingOnPublicShares(): void {
		$context = NodeAttributeContext::forShare($this->createMock(IShare::class));
		$node = $this->node(File::class, 11, '/bob/files/Shared/report.md');
		$this->shareManager->expects(self::never())->method('getSharesBy');

		$this->provider->preload($context, [$node], self::ATTRIBUTES);
		self::assertSame([], $this->provider->getAttributes($context, $node, self::ATTRIBUTES));
	}
}
