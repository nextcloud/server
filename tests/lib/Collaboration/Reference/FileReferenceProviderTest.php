<?php

declare(strict_types=1);

namespace Tests\OC\Collaboration\Reference;

use OC\Collaboration\Reference\File\FileReferenceProvider;
use OC\User\NoUserException;
use OCP\Collaboration\Reference\IReference;
use OCP\Files\IMimeTypeDetector;
use OCP\Files\IRootFolder;
use OCP\Files\InvalidPathException;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\IL10N;
use OCP\IPreview;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserSession;
use OCP\L10N\IFactory;
use OCP\Share\Exceptions\ShareNotFound;
use OCP\Share\IManager as ShareManager;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class FileReferenceProviderTest extends TestCase {
	private FileReferenceProvider $provider;
	private IURLGenerator|MockObject $urlGenerator;
	private IRootFolder|MockObject $rootFolder;
	private IUserSession|MockObject $userSession;
	private IMimeTypeDetector|MockObject $mimeTypeDetector;
	private IPreview|MockObject $previewManager;
	private IFactory|MockObject $l10nFactory;
	private ShareManager|MockObject $shareManager;
	private IL10N|MockObject $l10n;
	private IUser|MockObject $user;
	private string $host = 'https://example.com';

	protected function setUp(): void {
		parent::setUp();

		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->rootFolder = $this->createMock(IRootFolder::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->mimeTypeDetector = $this->createMock(IMimeTypeDetector::class);
		$this->previewManager = $this->createMock(IPreview::class);
		$this->l10nFactory = $this->createMock(IFactory::class);
		$this->shareManager = $this->createMock(ShareManager::class);
		$this->l10n = $this->createMock(IL10N::class);
		$this->user = $this->createMock(IUser::class);

		$this->l10nFactory->method('get')
			->with('files')
			->willReturn($this->l10n);

		$this->urlGenerator->method('getAbsoluteURL')
			->willReturnCallback(function ($path) {
				return $this->host . $path;
			});

		$this->userSession->method('getUser')
			->willReturn($this->user);
		$this->user->method('getUID')
			->willReturn('testuser');

		$this->provider = new FileReferenceProvider(
			$this->urlGenerator,
			$this->rootFolder,
			$this->userSession,
			$this->mimeTypeDetector,
			$this->previewManager,
			$this->l10nFactory,
			$this->shareManager
		);
	}

	/**
	 * @dataProvider matchReferenceDataProvider
	 */
	public function testMatchReference(string $referenceText, bool $expected): void {
		$this->assertEquals($expected, $this->provider->matchReference($referenceText));
	}

	public function matchReferenceDataProvider(): array {
		return [
			'file id in apps/files' => [
				'referenceText' => $this->host . '/apps/files/?fileid=123',
				'expected' => true
			],
			'file id in index.php/apps/files' => [
				'referenceText' => $this->host . '/index.php/apps/files/?fileid=123',
				'expected' => true
			],
			'openfile in apps/files' => [
				'referenceText' => $this->host . '/apps/files/?openfile=123',
				'expected' => true
			],
			'openfile in index.php/apps/files' => [
				'referenceText' => $this->host . '/index.php/apps/files/?openfile=123',
				'expected' => true
			],
			'public link with s/' => [
				'referenceText' => $this->host . '/s/abc123',
				'expected' => true
			],
			'public link with index.php/s/' => [
				'referenceText' => $this->host . '/index.php/s/abc123',
				'expected' => true
			],
			'public link with path' => [
				'referenceText' => $this->host . '/s/abc123/path/to/file',
				'expected' => true
			],
			'invalid url' => [
				'referenceText' => $this->host . '/invalid/path',
				'expected' => false
			],
			'empty url' => [
				'referenceText' => '',
				'expected' => false
			],
			'non-matching domain' => [
				'referenceText' => 'https://other-domain.com/apps/files/?fileid=123',
				'expected' => false
			]
		];
	}

	public function testResolveReferenceWithFileId(): void {
		$userFolder = $this->createMock(\OCP\Files\Folder::class);
		$file = $this->createMock(\OCP\Files\File::class);

		$this->rootFolder->method('getUserFolder')
			->with('testuser')
			->willReturn($userFolder);

		$userFolder->method('getFirstNodeById')
			->with(123)
			->willReturn($file);

		$file->method('getName')
			->willReturn('test.txt');
		$file->method('getMimetype')
			->willReturn('text/plain');
		$file->method('getId')
			->willReturn(123);
		$file->method('getSize')
			->willReturn(1024);
		$file->method('getMTime')
			->willReturn(1234567890);

		$this->previewManager->method('isMimeSupported')
			->with('text/plain')
			->willReturn(true);

		$this->urlGenerator->method('linkToRouteAbsolute')
			->willReturn($this->host . '/preview/123');

		$referenceText = $this->host . '/apps/files/?fileid=123';
		$reference = $this->provider->resolveReference($referenceText);

		$this->assertInstanceOf(IReference::class, $reference);
		$this->assertEquals('test.txt', $reference->getTitle());
		$this->assertEquals('text/plain', $reference->getDescription());
		$this->assertEquals($this->host . '/index.php/f/123', $reference->getUrl());
		$this->assertEquals($this->host . '/preview/123', $reference->getImageUrl());
	}

	public function testResolveReferenceWithPublicLink(): void {
		$share = $this->createMock(\OCP\Share\IShare::class);
		$node = $this->createMock(\OCP\Files\File::class);

		$this->shareManager->method('getShareByToken')
			->with('abc123')
			->willReturn($share);

		$share->method('getNode')
			->willReturn($node);

		$node->method('getName')
			->willReturn('public.txt');
		$node->method('getMimetype')
			->willReturn('text/plain');
		$node->method('getSize')
			->willReturn(2048);
		$node->method('getMTime')
			->willReturn(1234567890);

		$this->previewManager->method('isMimeSupported')
			->with('text/plain')
			->willReturn(true);

		$this->urlGenerator->method('linkToRouteAbsolute')
			->willReturn($this->host . '/preview/public/abc123');

		$referenceText = $this->host . '/s/abc123';
		$reference = $this->provider->resolveReferencePublic($referenceText, 'abc123');

		$this->assertInstanceOf(IReference::class, $reference);
		$this->assertEquals('public.txt', $reference->getTitle());
		$this->assertEquals('text/plain', $reference->getDescription());
		$this->assertEquals($this->host . '/s/abc123', $reference->getUrl());
		$this->assertEquals($this->host . '/preview/public/abc123', $reference->getImageUrl());
	}

	public function testResolveReferenceWithNotFoundFile(): void {
		$userFolder = $this->createMock(\OCP\Files\Folder::class);
		$this->rootFolder->method('getUserFolder')
			->with('testuser')
			->willReturn($userFolder);

		$userFolder->method('getFirstNodeById')
			->with(123)
			->willThrowException(new NotFoundException());

		$referenceText = $this->host . '/apps/files/?fileid=123';
		$reference = $this->provider->resolveReference($referenceText);

		$this->assertInstanceOf(IReference::class, $reference);
		$this->assertFalse($reference->getAccessible());
		$this->assertEquals([
			'id' => $referenceText,
			'name' => $referenceText,
			'description' => null,
			'thumb' => null,
			'link' => $referenceText
		], $reference->getRichObject());
	}

	public function testResolveReferenceWithInvalidPath(): void {
		$userFolder = $this->createMock(\OCP\Files\Folder::class);
		$this->rootFolder->method('getUserFolder')
			->with('testuser')
			->willReturn($userFolder);

		$userFolder->method('getFirstNodeById')
			->with(123)
			->willThrowException(new InvalidPathException());

		$referenceText = $this->host . '/apps/files/?fileid=123';
		$reference = $this->provider->resolveReference($referenceText);

		$this->assertInstanceOf(IReference::class, $reference);
		$this->assertFalse($reference->getAccessible());
		$this->assertEquals([
			'id' => $referenceText,
			'name' => $referenceText,
			'description' => null,
			'thumb' => null,
			'link' => $referenceText
		], $reference->getRichObject());
	}

	public function testGetCachePrefixWithFileId(): void {
		$referenceId = $this->host . '/apps/files/?fileid=123';
		$this->assertEquals('123', $this->provider->getCachePrefix($referenceId));
	}

	public function testGetCachePrefixWithPublicLink(): void {
		$referenceId = $this->host . '/s/abc123';
		$this->assertEquals('abc123', $this->provider->getCachePrefix($referenceId));
	}

	public function testGetCacheKey(): void {
		$this->assertEquals('testuser', $this->provider->getCacheKey('any-reference'));
	}

	public function testGetCacheKeyPublic(): void {
		$this->assertEquals('abc123', $this->provider->getCacheKeyPublic('any-reference', 'abc123'));
	}

	public function testGetId(): void {
		$this->assertEquals('files', $this->provider->getId());
	}

	public function testGetTitle(): void {
		$this->l10n->method('t')
			->with('Files')
			->willReturn('Files');

		$this->assertEquals('Files', $this->provider->getTitle());
	}

	public function testGetOrder(): void {
		$this->assertEquals(0, $this->provider->getOrder());
	}

	public function testGetIconUrl(): void {
		$this->urlGenerator->method('imagePath')
			->with('files', 'folder.svg')
			->willReturn('/path/to/folder.svg');

		$this->assertEquals('/path/to/folder.svg', $this->provider->getIconUrl());
	}
} 