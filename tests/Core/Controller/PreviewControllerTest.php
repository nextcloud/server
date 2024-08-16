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
use OCP\Files\Storage\IStorage;
use OCP\IPreview;
use OCP\IRequest;
use OCP\Preview\IMimeIconProvider;

class PreviewControllerTest extends \Test\TestCase {
	/** @var IRootFolder|\PHPUnit\Framework\MockObject\MockObject */
	private $rootFolder;

	/** @var string */
	private $userId;

	/** @var IPreview|\PHPUnit\Framework\MockObject\MockObject */
	private $previewManager;

	/** @var PreviewController|\PHPUnit\Framework\MockObject\MockObject */
	private $controller;

	protected function setUp(): void {
		parent::setUp();

		$this->rootFolder = $this->createMock(IRootFolder::class);
		$this->userId = 'user';
		$this->previewManager = $this->createMock(IPreview::class);

		$this->controller = new PreviewController(
			'core',
			$this->createMock(IRequest::class),
			$this->previewManager,
			$this->rootFolder,
			$this->userId,
			$this->createMock(IMimeIconProvider::class)
		);
	}

	public function testInvalidFile() {
		$res = $this->controller->getPreview('');
		$expected = new DataResponse([], Http::STATUS_BAD_REQUEST);

		$this->assertEquals($expected, $res);
	}

	public function testInvalidWidth() {
		$res = $this->controller->getPreview('file', 0);
		$expected = new DataResponse([], Http::STATUS_BAD_REQUEST);

		$this->assertEquals($expected, $res);
	}

	public function testInvalidHeight() {
		$res = $this->controller->getPreview('file', 10, 0);
		$expected = new DataResponse([], Http::STATUS_BAD_REQUEST);

		$this->assertEquals($expected, $res);
	}

	public function testFileNotFound() {
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

	public function testNotAFile() {
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

	public function testNoPreviewAndNoIcon() {
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

	public function testForbiddenFile() {
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

	public function testValidPreview() {
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
}
