<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Tests\Core\Controller;

use OC\Core\Controller\PreviewController;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\Files\SimpleFS\ISimpleFile;
use OCP\Files\Storage\ISharedStorage;
use OCP\Files\Storage\IStorage;
use OCP\IPreview;
use OCP\IRequest;
use OCP\Preview\IMimeIconProvider;
use OCP\Share\IAttributes;
use OCP\Share\IShare;
use PHPUnit\Framework\MockObject\MockObject;

class PreviewControllerTest extends \Test\TestCase {

	private string $userId;
	private PreviewController $controller;

	private IRootFolder&MockObject $rootFolder;
	private IPreview&MockObject $previewManager;
	private IRequest&MockObject $request;

	protected function setUp(): void {
		parent::setUp();

		$this->userId = 'user';
		$this->rootFolder = $this->createMock(IRootFolder::class);
		$this->previewManager = $this->createMock(IPreview::class);
		$this->request = $this->createMock(IRequest::class);

		$this->controller = new PreviewController(
			'core',
			$this->request,
			$this->previewManager,
			$this->rootFolder,
			$this->userId,
			$this->createMock(IMimeIconProvider::class)
		);
	}

	public function testInvalidFile(): void {
		$res = $this->controller->getPreview('');
		$expected = new DataResponse([], Http::STATUS_BAD_REQUEST);

		$this->assertEquals($expected, $res);
	}

	public function testInvalidWidth(): void {
		$res = $this->controller->getPreview('file', 0);
		$expected = new DataResponse([], Http::STATUS_BAD_REQUEST);

		$this->assertEquals($expected, $res);
	}

	public function testInvalidHeight(): void {
		$res = $this->controller->getPreview('file', 10, 0);
		$expected = new DataResponse([], Http::STATUS_BAD_REQUEST);

		$this->assertEquals($expected, $res);
	}

	public function testFileNotFound(): void {
		$userFolder = $this->createMock(Folder::class);
		$this->rootFolder->method('getUserFolder')
			->with($this->equalTo($this->userId))
			->willReturn($userFolder);

		$userFolder->method('get')
			->with($this->equalTo('file'))
			->willThrowException(new NotFoundException());

		$res = $this->controller->getPreview('file');
		$expected = new DataResponse([], Http::STATUS_NOT_FOUND);

		$this->assertEquals($expected, $res);
	}

	public function testNotAFile(): void {
		$userFolder = $this->createMock(Folder::class);
		$this->rootFolder->method('getUserFolder')
			->with($this->equalTo($this->userId))
			->willReturn($userFolder);

		$folder = $this->createMock(Folder::class);
		$userFolder->method('get')
			->with($this->equalTo('file'))
			->willReturn($folder);

		$res = $this->controller->getPreview('file');
		$expected = new DataResponse([], Http::STATUS_NOT_FOUND);

		$this->assertEquals($expected, $res);
	}

	public function testNoPreviewAndNoIcon(): void {
		$userFolder = $this->createMock(Folder::class);
		$this->rootFolder->method('getUserFolder')
			->with($this->equalTo($this->userId))
			->willReturn($userFolder);

		$file = $this->createMock(File::class);
		$userFolder->method('get')
			->with($this->equalTo('file'))
			->willReturn($file);

		$this->previewManager->method('isAvailable')
			->with($this->equalTo($file))
			->willReturn(false);

		$res = $this->controller->getPreview('file', 10, 10, true, false);
		$expected = new DataResponse([], Http::STATUS_NOT_FOUND);

		$this->assertEquals($expected, $res);
	}

	public function testNoPreview() {
		$userFolder = $this->createMock(Folder::class);
		$this->rootFolder->method('getUserFolder')
			->with($this->equalTo($this->userId))
			->willReturn($userFolder);

		$file = $this->createMock(File::class);
		$userFolder->method('get')
			->with($this->equalTo('file'))
			->willReturn($file);

		$storage = $this->createMock(IStorage::class);
		$file->method('getStorage')
			->willReturn($storage);

		$this->previewManager->method('isAvailable')
			->with($this->equalTo($file))
			->willReturn(true);

		$file->method('isReadable')
			->willReturn(true);

		$this->previewManager->method('getPreview')
			->with($this->equalTo($file), 10, 10, false, $this->equalTo('myMode'))
			->willThrowException(new NotFoundException());

		$res = $this->controller->getPreview('file', 10, 10, true, true, 'myMode');
		$expected = new DataResponse([], Http::STATUS_NOT_FOUND);

		$this->assertEquals($expected, $res);
	}
	public function testFileWithoutReadPermission() {
		$userFolder = $this->createMock(Folder::class);
		$this->rootFolder->method('getUserFolder')
			->with($this->equalTo($this->userId))
			->willReturn($userFolder);

		$file = $this->createMock(File::class);
		$userFolder->method('get')
			->with($this->equalTo('file'))
			->willReturn($file);

		$this->previewManager->method('isAvailable')
			->with($this->equalTo($file))
			->willReturn(true);

		$file->method('isReadable')
			->willReturn(false);

		$res = $this->controller->getPreview('file', 10, 10, true, true);
		$expected = new DataResponse([], Http::STATUS_FORBIDDEN);

		$this->assertEquals($expected, $res);
	}

	public function testFileWithoutDownloadPermission() {
		$userFolder = $this->createMock(Folder::class);
		$this->rootFolder->method('getUserFolder')
			->with($this->equalTo($this->userId))
			->willReturn($userFolder);

		$file = $this->createMock(File::class);
		$file->method('getId')->willReturn(123);
		$userFolder->method('get')
			->with($this->equalTo('file'))
			->willReturn($file);

		$this->previewManager->method('isAvailable')
			->with($this->equalTo($file))
			->willReturn(true);

		$shareAttributes = $this->createMock(IAttributes::class);
		$shareAttributes->expects(self::atLeastOnce())
			->method('getAttribute')
			->with('permissions', 'download')
			->willReturn(false);

		$share = $this->createMock(IShare::class);
		$share->method('getAttributes')
			->willReturn($shareAttributes);

		$storage = $this->createMock(ISharedStorage::class);
		$storage->method('instanceOfStorage')
			->with(ISharedStorage::class)
			->willReturn(true);
		$storage->method('getShare')
			->willReturn($share);

		$file->method('getStorage')
			->willReturn($storage);
		$file->method('isReadable')
			->willReturn(true);

		$this->request->method('getHeader')->willReturn('');

		$res = $this->controller->getPreview('file', 10, 10, true, true);
		$expected = new DataResponse([], Http::STATUS_FORBIDDEN);

		$this->assertEquals($expected, $res);
	}

	public function testFileWithoutDownloadPermissionButHeader() {
		$userFolder = $this->createMock(Folder::class);
		$this->rootFolder->method('getUserFolder')
			->with($this->equalTo($this->userId))
			->willReturn($userFolder);

		$file = $this->createMock(File::class);
		$file->method('getId')->willReturn(123);
		$userFolder->method('get')
			->with($this->equalTo('file'))
			->willReturn($file);

		$this->previewManager->method('isAvailable')
			->with($this->equalTo($file))
			->willReturn(true);

		$shareAttributes = $this->createMock(IAttributes::class);
		$shareAttributes->method('getAttribute')
			->with('permissions', 'download')
			->willReturn(false);

		$share = $this->createMock(IShare::class);
		$share->method('getAttributes')
			->willReturn($shareAttributes);

		$storage = $this->createMock(ISharedStorage::class);
		$storage->method('instanceOfStorage')
			->with(ISharedStorage::class)
			->willReturn(true);
		$storage->method('getShare')
			->willReturn($share);

		$file->method('getStorage')
			->willReturn($storage);
		$file->method('isReadable')
			->willReturn(true);

		$this->request
			->method('getHeader')
			->with('x-nc-preview')
			->willReturn('true');

		$preview = $this->createMock(ISimpleFile::class);
		$preview->method('getName')->willReturn('my name');
		$preview->method('getMTime')->willReturn(42);
		$this->previewManager->method('getPreview')
			->with($this->equalTo($file), 10, 10, false, $this->equalTo('myMode'))
			->willReturn($preview);
		$preview->method('getMimeType')
			->willReturn('myMime');

		$res = $this->controller->getPreview('file', 10, 10, true, true, 'myMode');

		$this->assertEquals('myMime', $res->getHeaders()['Content-Type']);
		$this->assertEquals(Http::STATUS_OK, $res->getStatus());
		$this->assertEquals($preview, $this->invokePrivate($res, 'file'));
	}

	public function testValidPreview(): void {
		$userFolder = $this->createMock(Folder::class);
		$this->rootFolder->method('getUserFolder')
			->with($this->equalTo($this->userId))
			->willReturn($userFolder);

		$file = $this->createMock(File::class);
		$file->method('getId')->willReturn(123);
		$userFolder->method('get')
			->with($this->equalTo('file'))
			->willReturn($file);

		$this->previewManager->method('isAvailable')
			->with($this->equalTo($file))
			->willReturn(true);

		$file->method('isReadable')
			->willReturn(true);

		$storage = $this->createMock(IStorage::class);
		$file->method('getStorage')
			->willReturn($storage);

		$preview = $this->createMock(ISimpleFile::class);
		$preview->method('getName')->willReturn('my name');
		$preview->method('getMTime')->willReturn(42);
		$this->previewManager->method('getPreview')
			->with($this->equalTo($file), 10, 10, false, $this->equalTo('myMode'))
			->willReturn($preview);
		$preview->method('getMimeType')
			->willReturn('myMime');

		$res = $this->controller->getPreview('file', 10, 10, true, true, 'myMode');

		$this->assertEquals('myMime', $res->getHeaders()['Content-Type']);
		$this->assertEquals(Http::STATUS_OK, $res->getStatus());
		$this->assertEquals($preview, $this->invokePrivate($res, 'file'));
	}

	public function testValidPreviewOfShare() {
		$userFolder = $this->createMock(Folder::class);
		$this->rootFolder->method('getUserFolder')
			->with($this->equalTo($this->userId))
			->willReturn($userFolder);

		$file = $this->createMock(File::class);
		$file->method('getId')->willReturn(123);
		$userFolder->method('get')
			->with($this->equalTo('file'))
			->willReturn($file);

		$this->previewManager->method('isAvailable')
			->with($this->equalTo($file))
			->willReturn(true);

		// No attributes set -> download permitted
		$share = $this->createMock(IShare::class);
		$share->method('getAttributes')
			->willReturn(null);

		$storage = $this->createMock(ISharedStorage::class);
		$storage->method('instanceOfStorage')
			->with(ISharedStorage::class)
			->willReturn(true);
		$storage->method('getShare')
			->willReturn($share);

		$file->method('getStorage')
			->willReturn($storage);
		$file->method('isReadable')
			->willReturn(true);

		$this->request
			->method('getHeader')
			->willReturn('');

		$preview = $this->createMock(ISimpleFile::class);
		$preview->method('getName')->willReturn('my name');
		$preview->method('getMTime')->willReturn(42);
		$this->previewManager->method('getPreview')
			->with($this->equalTo($file), 10, 10, false, $this->equalTo('myMode'))
			->willReturn($preview);
		$preview->method('getMimeType')
			->willReturn('myMime');

		$res = $this->controller->getPreview('file', 10, 10, true, true, 'myMode');

		$this->assertEquals('myMime', $res->getHeaders()['Content-Type']);
		$this->assertEquals(Http::STATUS_OK, $res->getStatus());
		$this->assertEquals($preview, $this->invokePrivate($res, 'file'));
	}
}
