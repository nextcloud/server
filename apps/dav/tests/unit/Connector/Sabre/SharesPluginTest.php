<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Tests\unit\Connector\Sabre;

use OCA\DAV\Connector\Sabre\Directory;
use OCA\DAV\Connector\Sabre\Exception\Forbidden;
use OCA\DAV\Connector\Sabre\File;
use OCA\DAV\Connector\Sabre\Node;
use OCA\DAV\Connector\Sabre\SharesPlugin;
use OCA\DAV\Upload\UploadFile;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\IUserFolder;
use OCP\Files\Mount\IMountPoint;
use OCP\Files\Mount\IShareOwnerlessMount;
use OCP\Files\Node as FileNode;
use OCP\Files\Storage\ISharedStorage;
use OCP\Files\Storage\IStorage;
use OCP\IUser;
use OCP\IUserSession;
use OCP\Share\IManager;
use OCP\Share\IShare;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\Tree;

class SharesPluginTest extends \Test\TestCase {
	public const SHARETYPES_PROPERTYNAME = SharesPlugin::SHARETYPES_PROPERTYNAME;

	private const SOURCE = 'files/user1/somedir/source.txt';
	private const TARGET = 'files/user1/othersdir/source.txt';
	private const TARGET_PARENT = 'files/user1/othersdir';
	private const USER_ROOT = '/user1/files';
	private const GROUP_FOLDER_MOUNT = '/user1/files/GF/';

	private \Sabre\DAV\Server $server;
	private \Sabre\DAV\Tree&MockObject $tree;
	private \OCP\Share\IManager&MockObject $shareManager;
	private Folder&MockObject $userFolder;
	private IRootFolder&MockObject $rootFolder;
	private SharesPlugin $plugin;
	private ?IUserFolder $userRoot = null;
	/** @var list<string> paths of the nodes getSharesBy() was called for */
	private array $sharesRequestedFor = [];

	protected function setUp(): void {
		parent::setUp();
		$this->server = new \Sabre\DAV\Server();
		$this->tree = $this->createMock(Tree::class);
		$this->shareManager = $this->createMock(IManager::class);
		$this->rootFolder = $this->createMock(IRootFolder::class);
		$user = $this->createMock(IUser::class);
		$user->expects($this->once())
			->method('getUID')
			->willReturn('user1');
		$userSession = $this->createMock(IUserSession::class);
		$userSession->expects($this->once())
			->method('getUser')
			->willReturn($user);
		$this->userFolder = $this->createMock(Folder::class);

		$this->plugin = new SharesPlugin(
			$this->tree,
			$userSession,
			$this->userFolder,
			$this->shareManager,
			$this->rootFolder,
		);
		$this->plugin->initialize($this->server);
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('sharesGetPropertiesDataProvider')]
	public function testGetProperties(array $shareTypes): void {
		$sabreNode = $this->createMock(Node::class);
		$sabreNode->expects($this->any())
			->method('getId')
			->willReturn(123);
		$sabreNode->expects($this->any())
			->method('getPath')
			->willReturn('/subdir');

		// node API nodes
		$node = $this->createMock(Folder::class);

		$sabreNode->method('getNode')
			->willReturn($node);

		$this->shareManager->expects($this->any())
			->method('getSharesBy')
			->with(
				$this->equalTo('user1'),
				$this->anything(),
				$this->equalTo($node),
				$this->equalTo(false),
				$this->equalTo(-1)
			)
			->willReturnCallback(function ($userId, $requestedShareType, $node, $flag, $limit) use ($shareTypes) {
				if (in_array($requestedShareType, $shareTypes)) {
					$share = $this->createMock(IShare::class);
					$share->method('getShareType')
						->willReturn($requestedShareType);
					return [$share];
				}
				return [];
			});

		$this->shareManager->expects($this->any())
			->method('getSharedWith')
			->with(
				$this->equalTo('user1'),
				$this->anything(),
				$this->equalTo($node),
				$this->equalTo(-1)
			)
			->willReturn([]);

		$propFind = new \Sabre\DAV\PropFind(
			'/dummyPath',
			[self::SHARETYPES_PROPERTYNAME],
			0
		);

		$this->plugin->handleGetProperties(
			$propFind,
			$sabreNode
		);

		$result = $propFind->getResultForMultiStatus();

		$this->assertEmpty($result[404]);
		unset($result[404]);
		$this->assertEquals($shareTypes, $result[200][self::SHARETYPES_PROPERTYNAME]->getShareTypes());
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('sharesGetPropertiesDataProvider')]
	public function testPreloadThenGetProperties(array $shareTypes): void {
		$sabreNode1 = $this->createMock(File::class);
		$sabreNode1->method('getId')
			->willReturn(111);
		$sabreNode2 = $this->createMock(File::class);
		$sabreNode2->method('getId')
			->willReturn(222);
		$sabreNode2->method('getPath')
			->willReturn('/subdir/foo');

		$sabreNode = $this->createMock(Directory::class);
		$sabreNode->method('getId')
			->willReturn(123);
		// never, because we use getDirectoryListing from the Node API instead
		$sabreNode->expects($this->never())
			->method('getChildren');
		$sabreNode->expects($this->any())
			->method('getPath')
			->willReturn('/subdir');

		// node API nodes
		$node = $this->createMock(Folder::class);
		$node->method('getId')
			->willReturn(123);
		$node1 = $this->createMock(\OC\Files\Node\File::class);
		$node1->method('getId')
			->willReturn(111);
		$node2 = $this->createMock(\OC\Files\Node\File::class);
		$node2->method('getId')
			->willReturn(222);

		$sabreNode->method('getNode')
			->willReturn($node);
		$sabreNode1->method('getNode')
			->willReturn($node1);
		$sabreNode2->method('getNode')
			->willReturn($node2);

		$dummyShares = array_map(function ($type) {
			$share = $this->createMock(IShare::class);
			$share->expects($this->any())
				->method('getShareType')
				->willReturn($type);
			return $share;
		}, $shareTypes);

		$this->shareManager->expects($this->any())
			->method('getSharesBy')
			->with(
				$this->equalTo('user1'),
				$this->anything(),
				$this->anything(),
				$this->equalTo(false),
				$this->equalTo(-1)
			)
			->willReturnCallback(function ($userId, $requestedShareType, $node, $flag, $limit) use ($shareTypes, $dummyShares) {
				if ($node->getId() === 111 && in_array($requestedShareType, $shareTypes)) {
					foreach ($dummyShares as $dummyShare) {
						if ($dummyShare->getShareType() === $requestedShareType) {
							return [$dummyShare];
						}
					}
				}

				return [];
			});

		$this->shareManager->expects($this->any())
			->method('getSharedWith')
			->with(
				$this->equalTo('user1'),
				$this->anything(),
				$this->equalTo($node),
				$this->equalTo(-1)
			)
			->willReturn([]);

		$this->shareManager->expects($this->any())
			->method('getSharesInFolder')
			->with(
				$this->equalTo('user1'),
				$this->anything(),
				$this->equalTo(true)
			)
			->willReturnCallback(function ($userId, $node, $flag) use ($shareTypes, $dummyShares) {
				return [111 => $dummyShares];
			});

		// simulate sabre recursive PROPFIND traversal
		$propFindRoot = new \Sabre\DAV\PropFind(
			'/subdir',
			[self::SHARETYPES_PROPERTYNAME],
			1
		);
		$propFind1 = new \Sabre\DAV\PropFind(
			'/subdir/test.txt',
			[self::SHARETYPES_PROPERTYNAME],
			0
		);
		$propFind2 = new \Sabre\DAV\PropFind(
			'/subdir/test2.txt',
			[self::SHARETYPES_PROPERTYNAME],
			0
		);

		$this->server->emit('preloadCollection', [$propFindRoot, $sabreNode]);
		$this->plugin->handleGetProperties(
			$propFindRoot,
			$sabreNode
		);
		$this->plugin->handleGetProperties(
			$propFind1,
			$sabreNode1
		);
		$this->plugin->handleGetProperties(
			$propFind2,
			$sabreNode2
		);

		$result = $propFind1->getResultForMultiStatus();

		$this->assertEmpty($result[404]);
		unset($result[404]);
		$this->assertEquals($shareTypes, $result[200][self::SHARETYPES_PROPERTYNAME]->getShareTypes());
	}

	public static function sharesGetPropertiesDataProvider(): array {
		return [
			[[]],
			[[IShare::TYPE_USER]],
			[[IShare::TYPE_GROUP]],
			[[IShare::TYPE_LINK]],
			[[IShare::TYPE_REMOTE]],
			[[IShare::TYPE_ROOM]],
			[[IShare::TYPE_DECK]],
			[[IShare::TYPE_SCIENCEMESH]],
			[[IShare::TYPE_USER, IShare::TYPE_GROUP]],
			[[IShare::TYPE_USER, IShare::TYPE_GROUP, IShare::TYPE_LINK]],
			[[IShare::TYPE_USER, IShare::TYPE_LINK]],
			[[IShare::TYPE_GROUP, IShare::TYPE_LINK]],
			[[IShare::TYPE_USER, IShare::TYPE_REMOTE]],
		];
	}

	public function testGetPropertiesSkipChunks(): void {
		$sabreNode = $this->createMock(UploadFile::class);

		$propFind = new \Sabre\DAV\PropFind(
			'/dummyPath',
			[self::SHARETYPES_PROPERTYNAME],
			0
		);

		$this->plugin->handleGetProperties(
			$propFind,
			$sabreNode
		);

		$result = $propFind->getResultForMultiStatus();
		$this->assertCount(1, $result[404]);
	}

	private function mockShare(string $id): IShare&MockObject {
		$share = $this->createMock(IShare::class);
		$share->method('getId')
			->willReturn($id);

		return $share;
	}

	private function mockStorage(?IShare $share = null): (IStorage&MockObject)|(ISharedStorage&MockObject) {
		$storage = $this->createMock($share === null ? IStorage::class : ISharedStorage::class);

		if ($share !== null) {
			$storage->method('getShare')
				->willReturn($share);
			$storage->method('instanceOfStorage')
				->willReturnCallback(static fn (string $class): bool => $class === ISharedStorage::class);
		}

		return $storage;
	}

	private function mockSourceNode(
		IStorage $storage,
		bool $shareable = false,
		bool $deletable = false,
		string $internalPath = 'source.txt',
		?IMountPoint $mountPoint = null,
		?Folder $parent = null,
	): FileNode&MockObject {
		$node = $this->createMock(FileNode::class);
		$node->method('getStorage')
			->willReturn($storage);
		$node->method('isShareable')
			->willReturn($shareable);
		$node->method('isDeletable')
			->willReturn($deletable);
		$node->method('getInternalPath')
			->willReturn($internalPath);
		$node->method('getMountPoint')
			->willReturn($mountPoint);
		if ($parent !== null) {
			$node->method('getParent')
				->willReturn($parent);
		}

		return $node;
	}

	private function mockMount(string $mountPoint, bool $shareOwnerless): IMountPoint&MockObject {
		/** @var IMountPoint&MockObject $mount */
		$mount = $shareOwnerless
			? $this->createMockForIntersectionOfInterfaces([IMountPoint::class, IShareOwnerlessMount::class])
			: $this->createMock(IMountPoint::class);
		$mount->method('getMountPoint')
			->willReturn($mountPoint);

		return $mount;
	}

	private function mockUserRoot(): IUserFolder {
		if ($this->userRoot === null) {
			$userRoot = $this->createMock(IUserFolder::class);
			$userRoot->method('getPath')
				->willReturn(self::USER_ROOT);
			$this->rootFolder->method('getUserFolder')
				->with('user1')
				->willReturn($userRoot);
			$this->userRoot = $userRoot;
		}

		return $this->userRoot;
	}

	/**
	 * Mocks the folder at $path, with its ancestors up to the user root as parents
	 */
	private function mockFolder(string $path, IMountPoint $mountPoint): Folder&MockObject {
		$parent = $this->mockUserRoot();
		$currentPath = self::USER_ROOT;
		foreach (explode('/', substr($path, strlen(self::USER_ROOT) + 1)) as $name) {
			$currentPath .= '/' . $name;
			$folder = $this->createMock(Folder::class);
			$folder->method('getPath')
				->willReturn($currentPath);
			$folder->method('getParent')
				->willReturn($parent);
			$folder->method('getMountPoint')
				->willReturn($mountPoint);
			$parent = $folder;
		}

		return $folder;
	}

	/**
	 * @param array<string, list<string>> $shareIdsByPath ids of the shares returned by getSharesBy() for a node path
	 * @param array<string, list<string>> $receivedIdsByPath ids of the shares returned by getSharedWith() for a node path
	 */
	private function mockSharesByPath(array $shareIdsByPath, array $receivedIdsByPath): void {
		$sharesByPath = array_map(fn (array $ids): array => array_map($this->mockShare(...), $ids), $shareIdsByPath);
		$receivedByPath = array_map(fn (array $ids): array => array_map($this->mockShare(...), $ids), $receivedIdsByPath);

		$this->shareManager->method('getSharesBy')
			->willReturnCallback(function (string $userId, int $shareType, FileNode $node) use ($sharesByPath): array {
				$this->sharesRequestedFor[] = $node->getPath();
				return $shareType === IShare::TYPE_USER ? ($sharesByPath[$node->getPath()] ?? []) : [];
			});
		$this->shareManager->method('getSharedWith')
			->willReturnCallback(static fn (string $userId, int $shareType, FileNode $node): array
				=> $shareType === IShare::TYPE_USER ? ($receivedByPath[$node->getPath()] ?? []) : []);
	}

	/**
	 * @param ?IShare $share the share the target is part of, or null when the target is not shared
	 */
	private function mockTargetNode(?IShare $share): FileNode&MockObject {
		$node = $this->createMock(FileNode::class);
		$node->method('getPath')
			->willReturn(self::USER_ROOT . '/othersdir');

		$this->shareManager->method('getSharesBy')
			->willReturn([]);
		$this->shareManager->method('getSharedWith')
			->willReturnCallback(static fn (string $userId, int $shareType): array
				=> ($share !== null && $shareType === IShare::TYPE_USER) ? [$share] : []);

		if ($share === null) {
			// when the target itself is not shared, getSharesForNode() walks up to the user root
			$node->method('getParent')
				->willReturn($this->mockUserRoot());
		}

		return $node;
	}

	/**
	 * @param array<string, FileNode> $nodes nodes keyed by their dav path
	 * @param list<string> $missing paths the tree should report as not found
	 */
	private function mockTree(array $nodes, array $missing = []): void {
		$this->tree->method('getNodeForPath')
			->willReturnCallback(function (string $path) use ($nodes, $missing): Node {
				if (in_array($path, $missing, true)) {
					throw new NotFound($path);
				}

				$this->assertArrayHasKey($path, $nodes, 'unexpected tree lookup');
				$sabreNode = $this->createMock(Node::class);
				$sabreNode->method('getNode')
					->willReturn($nodes[$path]);
				return $sabreNode;
			});
	}

	public function testValidateMoveOrCopyAllowsRenameInSameFolder(): void {
		$this->tree->expects($this->never())
			->method('getNodeForPath');

		$this->assertTrue($this->plugin->validateMoveOrCopy(self::SOURCE, 'files/user1/somedir/renamed.txt'));
	}

	public function testValidateMoveOrCopyAllowsShareableSource(): void {
		$this->shareManager->expects($this->never())
			->method('getSharesBy');
		$this->shareManager->expects($this->never())
			->method('getSharedWith');

		$this->mockTree([
			self::SOURCE => $this->mockSourceNode($this->mockStorage(), shareable: true),
			self::TARGET => $this->createMock(FileNode::class),
		]);

		$result = $this->plugin->validateMoveOrCopy(self::SOURCE, self::TARGET);
		$this->assertTrue($result);
	}

	public function testValidateMoveOrCopyResolvesParentOfMissingTarget(): void {
		$this->mockTree(
			[
				self::SOURCE => $this->mockSourceNode($this->mockStorage()),
				self::TARGET_PARENT => $this->mockTargetNode($this->mockShare('1')),
			],
			[self::TARGET],
		);

		$this->expectException(Forbidden::class);
		$this->expectExceptionMessage('You cannot move a non-shareable node into a share');

		$this->plugin->validateMoveOrCopy(self::SOURCE, self::TARGET);
	}

	public static function validateMoveOrCopyProvider(): array {
		// source share id (null: not a share), target share id (null: not shared), source deletable,
		// source internal path, source on a share-ownerless mount, allowed
		return [
			'target not shared' => [null, null, false, 'source.txt', false, true],
			'non-shareable source into a share' => [null, '1', false, 'source.txt', false, false],
			'within the same share' => ['42', '42', false, 'source.txt', false, true],
			'deletable share source into another share' => ['1', '2', true, 'source.txt', false, true],
			'non-deletable share source into another share' => ['1', '2', false, 'source.txt', false, false],
			'share root into another share' => ['1', '2', true, '', false, false],
			// a received share on a share-ownerless mount is handled as a share
			'share root on a share-ownerless mount into another share' => ['1', '2', true, '', true, false],
		];
	}

	#[DataProvider(methodName: 'validateMoveOrCopyProvider')]
	public function testValidateMoveOrCopy(
		?string $sourceShareId,
		?string $targetShareId,
		bool $deletable,
		string $internalPath,
		bool $ownerlessMount,
		bool $allowed,
	): void {
		$sourceNode = $this->mockSourceNode(
			$sourceShareId === null ? $this->mockStorage() : $this->mockStorage($this->mockShare($sourceShareId)),
			deletable: $deletable,
			internalPath: $internalPath,
			mountPoint: $ownerlessMount ? $this->mockMount(self::GROUP_FOLDER_MOUNT, true) : null,
		);
		$targetNode = $this->mockTargetNode($targetShareId === null ? null : $this->mockShare($targetShareId));

		if (!$allowed) {
			$this->expectException(Forbidden::class);
			$this->expectExceptionMessage('You cannot move a non-shareable node into a share');
		}

		$this->mockTree([
			self::SOURCE => $sourceNode,
			self::TARGET => $targetNode,
		]);

		$result = $this->plugin->validateMoveOrCopy(self::SOURCE, self::TARGET);

		$this->assertTrue($result);
	}

	public static function validateMoveOrCopyFromOwnerlessMountProvider(): array {
		// source parent, target, target mount point, target mount is share-ownerless, share ids by path,
		// received share ids by path, paths whose shares must not be looked up, allowed
		return [
			'target below the same shares' => [
				'/user1/files/GF/A/B', '/user1/files/GF/A/C', self::GROUP_FOLDER_MOUNT, true,
				['/user1/files/GF/A' => ['1']], [], ['/user1/files/GF/A'], true,
			],
			'target is a new share' => [
				'/user1/files/GF/B', '/user1/files/GF/A', self::GROUP_FOLDER_MOUNT, true,
				['/user1/files/GF/A' => ['1']], [], [], false,
			],
			'target below a new share' => [
				'/user1/files/GF/B', '/user1/files/GF/A/L/deeper', self::GROUP_FOLDER_MOUNT, true,
				['/user1/files/GF/A' => ['1']], [], [], false,
			],
			'share above the target mount' => [
				'/user1/files/GF/B', '/user1/files/Projects/ext/sub', '/user1/files/Projects/ext/', true,
				['/user1/files/Projects' => ['1']], [], ['/user1/files/Projects'], true,
			],
			'shared folder on another mount' => [
				'/user1/files/GF/B', '/user1/files/Public/sub', '/user1/', false,
				['/user1/files/Public' => ['1']], [], [], false,
			],
			'received share' => [
				'/user1/files/GF/B', '/user1/files/Received', '/user1/files/Received/', false,
				[], ['/user1/files/Received' => ['1']], [], false,
			],
			'below a received share' => [
				'/user1/files/GF/B', '/user1/files/Received/sub', '/user1/files/Received/', false,
				[], ['/user1/files/Received' => ['1']], [], false,
			],
		];
	}

	#[DataProvider(methodName: 'validateMoveOrCopyFromOwnerlessMountProvider')]
	public function testValidateMoveOrCopyFromOwnerlessMount(
		string $sourceParentPath,
		string $targetPath,
		string $targetMountPoint,
		bool $targetMountIsOwnerless,
		array $shareIdsByPath,
		array $receivedIdsByPath,
		array $notLookedUp,
		bool $allowed,
	): void {
		$this->mockSharesByPath($shareIdsByPath, $receivedIdsByPath);

		if (!$allowed) {
			$this->expectException(Forbidden::class);
			$this->expectExceptionMessage('You cannot move a non-shareable node into a share');
		}

		$targetMount = $this->mockMount($targetMountPoint, $targetMountIsOwnerless);
		$groupFolder = $this->mockMount(self::GROUP_FOLDER_MOUNT, true);
		$sourceNode = $this->mockSourceNode(
			$this->mockStorage(),
			mountPoint: $groupFolder,
			parent: $this->mockFolder($sourceParentPath, $groupFolder),
		);

		$davSource = 'files/user1' . substr($sourceParentPath, strlen(self::USER_ROOT)) . '/source.txt';
		$davTargetParent = 'files/user1' . substr($targetPath, strlen(self::USER_ROOT));
		$this->mockTree(
			[
				$davSource => $sourceNode,
				$davTargetParent => $this->mockFolder($targetPath, $targetMount),
			],
			[$davTargetParent . '/source.txt'],
		);

		$result = $this->plugin->validateMoveOrCopy($davSource, $davTargetParent . '/source.txt');

		$this->assertTrue($result);

		foreach ($notLookedUp as $path) {
			$this->assertNotContains($path, $this->sharesRequestedFor);
		}
	}
}
