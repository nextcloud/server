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
use OCP\Files\IUserFolder;
use OCP\Files\NotFoundException;
use OCP\Files\SimpleFS\ISimpleFile;
use OCP\Files\Storage\ISharedStorage;
use OCP\Files\Storage\IStorage;
use OCP\IPreview;
use OCP\IRequest;
use OCP\Preview\IMimeIconProvider;
use OCP\Share\IShare;
use PHPUnit\Framework\MockObject\MockObject;

class PreviewControllerTest extends \Test\TestCase {

	private string $userId;
	private PreviewController $controller;

	private IUserFolder&MockObject $userFolder;
	private IPreview&MockObject $previewManager;
	private IRequest&MockObject $request;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->userFolder = $this->createMock(IUserFolder::class);
		$this->previewManager = $this->createMock(IPreview::class);
		$this->request = $this->createMock(IRequest::class);

		$this->controller = new PreviewController(
			'core',
			$this->request,
			$this->previewManager,
			$this->userFolder,
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
		$this->userFolder->method('get')
			->with($this->equalTo('file'))
			->willThrowException(new NotFoundException());

		$res = $this->controller->getPreview('file');
		$expected = new DataResponse([], Http::STATUS_NOT_FOUND);

		$this->assertEquals($expected, $res);
	}

	public function testNotAFile(): void {
		$folder = $this->createMock(Folder::class);
		$this->userFolder->method('get')
			->with($this->equalTo('file'))
			->willReturn($folder);

		$res = $this->controller->getPreview('file');
		$expected = new DataResponse([], Http::STATUS_NOT_FOUND);

		$this->assertEquals($expected, $res);
	}

	public function testNoPreviewAndNoIcon(): void {
		$file = $this->createMock(File::class);
		$this->userFolder->method('get')
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
		$file = $this->createMock(File::class);
		$this->userFolder->method('get')
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
		$file = $this->createMock(File::class);
		$this->userFolder->method('get')
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
		$file = $this->createMock(File::class);
		$file->method('getId')->willReturn(123);
		$this->userFolder->method('get')
			->with($this->equalTo('file'))
			->willReturn($file);

		$this->previewManager->method('isAvailable')
			->with($this->equalTo($file))
			->willReturn(true);

		$share = $this->createMock(IShare::class);
		$share->method('canSeeContent')
			->willReturn(false);

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
		$file = $this->createMock(File::class);
		$file->method('getId')->willReturn(123);
		$this->userFolder->method('get')
			->with($this->equalTo('file'))
			->willReturn($file);

		$this->previewManager->method('isAvailable')
			->with($this->equalTo($file))
			->willReturn(true);

		$share = $this->createMock(IShare::class);
		$share->method('canSeeContent')
			->willReturn(false);

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
		$file = $this->createMock(File::class);
		$file->method('getId')->willReturn(123);
		$this->userFolder->method('get')
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
		$file = $this->createMock(File::class);
		$file->method('getId')->willReturn(123);
		$this->userFolder->method('get')
			->with($this->equalTo('file'))
			->willReturn($file);

		$this->previewManager->method('isAvailable')
			->with($this->equalTo($file))
			->willReturn(true);

		// No attributes set -> download permitted
		$share = $this->createMock(IShare::class);
		$share->method('canSeeContent')
			->willReturn(true);

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
