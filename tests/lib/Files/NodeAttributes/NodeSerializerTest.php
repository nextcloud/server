<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Files\NodeAttributes;

use NCU\Files\NodeAttributes\INodeAttributeProvider;
use NCU\Files\NodeAttributes\NodeAttributeContext;
use NCU\Files\NodeAttributes\NodeAttributeList;
use OC\Files\NodeAttributes\NodeAttributeRegistry;
use OC\Files\NodeAttributes\NodeSerializer;
use OCP\Files\File;
use OCP\Files\FileInfo;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\IUserFolder;
use OCP\Files\Node;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\Share\IShare;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class NodeSerializerTest extends TestCase {
	private const HAS_PREVIEW = '{http://nextcloud.org/ns}has-preview';
	private const SHARE_TYPES = '{http://owncloud.org/ns}share-types';

	private NodeAttributeRegistry&MockObject $registry;
	private IRootFolder&MockObject $rootFolder;
	private NodeSerializer $serializer;
	private IUser&MockObject $user;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->registry = $this->createMock(NodeAttributeRegistry::class);
		$this->rootFolder = $this->createMock(IRootFolder::class);
		$urlGenerator = $this->createMock(IURLGenerator::class);
		$urlGenerator->method('getAbsoluteURL')->willReturnCallback(static fn (string $url): string => 'https://cloud.example.com' . $url);

		$this->user = $this->createMock(IUser::class);
		$this->user->method('getUID')->willReturn('alice');
		$userFolder = $this->createMock(IUserFolder::class);
		$userFolder->method('getPath')->willReturn('/alice/files');
		$this->rootFolder->method('getUserFolder')->with('alice')->willReturn($userFolder);

		$this->serializer = new NodeSerializer($this->registry, $this->rootFolder, $urlGenerator, $this->createMock(LoggerInterface::class));
	}

	private function node(string $class, int $id, string $path, string $mime = 'text/plain'): Node&MockObject {
		$node = $this->createMock($class);
		$node->method('getId')->willReturn($id);
		$node->method('getPath')->willReturn($path);
		$node->method('getName')->willReturn(basename($path));
		$node->method('getType')->willReturn($class === Folder::class ? FileInfo::TYPE_FOLDER : FileInfo::TYPE_FILE);
		$node->method('getMimetype')->willReturn($mime);
		$node->method('getMTime')->willReturn(1760000000);
		$node->method('getCreationTime')->willReturn(0);
		$node->method('getSize')->willReturn(42);
		$node->method('getPermissions')->willReturn(31);
		$node->method('getEtag')->willReturn('etag-' . $id);
		$node->method('getOwner')->willReturn($this->user);
		return $node;
	}

	public function testSerializeForUser(): void {
		$file = $this->node(File::class, 11, '/alice/files/Photos/cat.jpg', 'image/jpeg');
		$folder = $this->node(Folder::class, 12, '/alice/files/Photos', 'httpd/unix-directory');
		$provider = $this->createMock(INodeAttributeProvider::class);
		$this->registry->method('getAttributeNames')->willReturn([self::HAS_PREVIEW, self::SHARE_TYPES]);
		$this->registry->method('getProvidersFor')
			->with([self::HAS_PREVIEW, self::SHARE_TYPES])
			->willReturn([['provider' => $provider, 'attributes' => [self::HAS_PREVIEW, self::SHARE_TYPES]]]);

		// Everything is preloaded at once, before reading any node
		$provider->expects(self::once())
			->method('preload')
			->with(self::isInstanceOf(NodeAttributeContext::class), [$file, $folder], [self::HAS_PREVIEW, self::SHARE_TYPES]);
		$provider->method('getAttributes')->willReturnCallback(static fn (NodeAttributeContext $context, Node $node): array => [
			self::HAS_PREVIEW => $node === $file,
			self::SHARE_TYPES => new NodeAttributeList('share-type', [0, 3]),
			'{DAV:}not-requested' => 'ignored',
		]);

		$result = $this->serializer->serialize(NodeAttributeContext::forUser($this->user), [$file, $folder]);

		self::assertSame([
			[
				'id' => 11,
				'source' => 'https://cloud.example.com/remote.php/dav/files/alice/Photos/cat.jpg',
				'root' => '/files/alice',
				'owner' => 'alice',
				'mime' => 'image/jpeg',
				'mtime' => 1760000000,
				'crtime' => null,
				'size' => 42,
				'permissions' => 31,
				'displayname' => 'cat.jpg',
				'type' => 'file',
				'attributes' => [
					'etag' => 'etag-11',
					'has-preview' => true,
					'share-types' => ['share-type' => [0, 3]],
				],
			],
			[
				'id' => 12,
				'source' => 'https://cloud.example.com/remote.php/dav/files/alice/Photos',
				'root' => '/files/alice',
				'owner' => 'alice',
				'mime' => 'httpd/unix-directory',
				'mtime' => 1760000000,
				'crtime' => null,
				'size' => 42,
				'permissions' => 31,
				'displayname' => 'Photos',
				'type' => 'folder',
				'attributes' => [
					'etag' => 'etag-12',
					'has-preview' => false,
					'share-types' => ['share-type' => [0, 3]],
				],
			],
		], $result);
	}

	public function testSerializeForShare(): void {
		$sharedFolder = $this->node(Folder::class, 20, '/bob/files/Shared');
		$file = $this->node(File::class, 21, '/bob/files/Shared/sub/doc.md');
		$share = $this->createMock(IShare::class);
		$share->method('getToken')->willReturn('AbCdEf');
		$share->method('getNode')->willReturn($sharedFolder);
		$this->registry->method('getAttributeNames')->willReturn([]);
		$this->registry->method('getProvidersFor')->willReturn([]);

		$result = $this->serializer->serialize(NodeAttributeContext::forShare($share), [$sharedFolder, $file]);

		self::assertSame('/files/AbCdEf', $result[0]['root']);
		self::assertSame('https://cloud.example.com/public.php/dav/files/AbCdEf', $result[0]['source']);
		self::assertSame('https://cloud.example.com/public.php/dav/files/AbCdEf/sub/doc.md', $result[1]['source']);
	}

	public function testOnlyRequestedAttributes(): void {
		$file = $this->node(File::class, 11, '/alice/files/cat.jpg');
		$this->registry->expects(self::never())->method('getAttributeNames');
		$this->registry->expects(self::once())
			->method('getProvidersFor')
			->with([self::HAS_PREVIEW])
			->willReturn([]);

		$result = $this->serializer->serialize(NodeAttributeContext::forUser($this->user), [$file], [self::HAS_PREVIEW]);

		self::assertSame(['etag' => 'etag-11'], $result[0]['attributes']);
	}

	public function testNodesOutsideOfTheRootAreSkipped(): void {
		$inside = $this->node(File::class, 11, '/alice/files/cat.jpg');
		$outside = $this->node(File::class, 12, '/bob/files/cat.jpg');
		$sibling = $this->node(File::class, 13, '/alice/files_trashbin/files/cat.jpg');
		$this->registry->method('getAttributeNames')->willReturn([]);
		$this->registry->method('getProvidersFor')->willReturn([]);

		$result = $this->serializer->serialize(NodeAttributeContext::forUser($this->user), [$inside, $outside, $sibling]);

		self::assertSame([11], array_column($result, 'id'));
	}
}
