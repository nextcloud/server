<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_Sharing\Tests\Controller;

use OCA\Files_Sharing\Controller\PublicPreviewController;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\FileDisplayResponse;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Constants;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\NotFoundException;
use OCP\Files\SimpleFS\ISimpleFile;
use OCP\IPreview;
use OCP\IRequest;
use OCP\ISession;
use OCP\Preview\IMimeIconProvider;
use OCP\Share\Exceptions\ShareNotFound;
use OCP\Share\IAttributes;
use OCP\Share\IManager;
use OCP\Share\IShare;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class PublicPreviewControllerTest extends TestCase {

	private IPreview&MockObject $previewManager;
	private IManager&MockObject $shareManager;
	private ITimeFactory&MockObject $timeFactory;
	private IRequest&MockObject $request;

	private PublicPreviewController $controller;

	protected function setUp(): void {
		parent::setUp();

		$this->previewManager = $this->createMock(IPreview::class);
		$this->shareManager = $this->createMock(IManager::class);
		$this->timeFactory = $this->createMock(ITimeFactory::class);
		$this->request = $this->createMock(IRequest::class);

		$this->timeFactory->method('getTime')
			->willReturn(1337);

		$this->overwriteService(ITimeFactory::class, $this->timeFactory);

		$this->controller = new PublicPreviewController(
			'files_sharing',
			$this->request,
			$this->shareManager,
			$this->createMock(ISession::class),
			$this->previewManager,
			$this->createMock(IMimeIconProvider::class),
		);
	}

	public function testInvalidToken(): void {
		$res = $this->controller->getPreview('', 'file', 10, 10, '');
		$expected = new DataResponse([], Http::STATUS_BAD_REQUEST);

		$this->assertEquals($expected, $res);
	}

	public function testInvalidWidth(): void {
		$res = $this->controller->getPreview('token', 'file', 0);
		$expected = new DataResponse([], Http::STATUS_BAD_REQUEST);

		$this->assertEquals($expected, $res);
	}

	public function testInvalidHeight(): void {
		$res = $this->controller->getPreview('token', 'file', 10, 0);
		$expected = new DataResponse([], Http::STATUS_BAD_REQUEST);

		$this->assertEquals($expected, $res);
	}

	public function testInvalidShare(): void {
		$this->shareManager->method('getShareByToken')
			->with($this->equalTo('token'))
			->willThrowException(new ShareNotFound());

		$res = $this->controller->getPreview('token', 'file', 10, 10);
		$expected = new DataResponse([], Http::STATUS_NOT_FOUND);

		$this->assertEquals($expected, $res);
	}

	public function testShareNotAccessable(): void {
		$share = $this->createMock(IShare::class);
		$this->shareManager->method('getShareByToken')
			->with($this->equalTo('token'))
			->willReturn($share);

		$share->method('getPermissions')
			->willReturn(0);

		$res = $this->controller->getPreview('token', 'file', 10, 10);
		$expected = new DataResponse([], Http::STATUS_FORBIDDEN);

		$this->assertEquals($expected, $res);
	}

	public function testShareNoDownload() {
		$share = $this->createMock(IShare::class);
		$this->shareManager->method('getShareByToken')
			->with($this->equalTo('token'))
			->willReturn($share);

		$share->method('getPermissions')
			->willReturn(Constants::PERMISSION_READ);

		$attributes = $this->createMock(IAttributes::class);
		$attributes->method('getAttribute')
			->with('permissions', 'download')
			->willReturn(false);
		$share->method('getAttributes')
			->willReturn($attributes);

		$res = $this->controller->getPreview('token', 'file', 10, 10);
		$expected = new DataResponse([], Http::STATUS_FORBIDDEN);

		$this->assertEquals($expected, $res);
	}

	public function testShareNoDownloadButPreviewHeader() {
		$share = $this->createMock(IShare::class);
		$this->shareManager->method('getShareByToken')
			->with($this->equalTo('token'))
			->willReturn($share);

		$share->method('getPermissions')
			->willReturn(Constants::PERMISSION_READ);

		$attributes = $this->createMock(IAttributes::class);
		$attributes->method('getAttribute')
			->with('permissions', 'download')
			->willReturn(false);
		$share->method('getAttributes')
			->willReturn($attributes);

		$this->request->method('getHeader')
			->with('x-nc-preview')
			->willReturn('true');

		$file = $this->createMock(File::class);
		$share->method('getNode')
			->willReturn($file);

		$preview = $this->createMock(ISimpleFile::class);
		$preview->method('getName')->willReturn('name');
		$preview->method('getMTime')->willReturn(42);
		$this->previewManager->method('getPreview')
			->with($this->equalTo($file), 10, 10, false)
			->willReturn($preview);

		$preview->method('getMimeType')
			->willReturn('myMime');

		$res = $this->controller->getPreview('token', 'file', 10, 10, true);
		$expected = new FileDisplayResponse($preview, Http::STATUS_OK, ['Content-Type' => 'myMime']);
		$expected->cacheFor(15 * 60);
		$this->assertEquals($expected, $res);
	}

	public function testShareWithAttributes() {
		$share = $this->createMock(IShare::class);
		$this->shareManager->method('getShareByToken')
			->with($this->equalTo('token'))
			->willReturn($share);

		$share->method('getPermissions')
			->willReturn(Constants::PERMISSION_READ);

		$attributes = $this->createMock(IAttributes::class);
		$attributes->method('getAttribute')
			->with('permissions', 'download')
			->willReturn(true);
		$share->method('getAttributes')
			->willReturn($attributes);

		$this->request->method('getHeader')
			->with('x-nc-preview')
			->willReturn('true');

		$file = $this->createMock(File::class);
		$share->method('getNode')
			->willReturn($file);

		$preview = $this->createMock(ISimpleFile::class);
		$preview->method('getName')->willReturn('name');
		$preview->method('getMTime')->willReturn(42);
		$this->previewManager->method('getPreview')
			->with($this->equalTo($file), 10, 10, false)
			->willReturn($preview);

		$preview->method('getMimeType')
			->willReturn('myMime');

		$res = $this->controller->getPreview('token', 'file', 10, 10, true);
		$expected = new FileDisplayResponse($preview, Http::STATUS_OK, ['Content-Type' => 'myMime']);
		$expected->cacheFor(3600 * 24);
		$this->assertEquals($expected, $res);
	}

	public function testPreviewFile() {
		$share = $this->createMock(IShare::class);
		$this->shareManager->method('getShareByToken')
			->with($this->equalTo('token'))
			->willReturn($share);

		$share->method('getPermissions')
			->willReturn(Constants::PERMISSION_READ);

		$file = $this->createMock(File::class);
		$share->method('getNode')
			->willReturn($file);

		$preview = $this->createMock(ISimpleFile::class);
		$preview->method('getName')->willReturn('name');
		$preview->method('getMTime')->willReturn(42);
		$this->previewManager->method('getPreview')
			->with($this->equalTo($file), 10, 10, false)
			->willReturn($preview);

		$preview->method('getMimeType')
			->willReturn('myMime');

		$res = $this->controller->getPreview('token', 'file', 10, 10, true);
		$expected = new FileDisplayResponse($preview, Http::STATUS_OK, ['Content-Type' => 'myMime']);
		$expected->cacheFor(3600 * 24);
		$this->assertEquals($expected, $res);
	}

	public function testPreviewFolderInvalidFile(): void {
		$share = $this->createMock(IShare::class);
		$this->shareManager->method('getShareByToken')
			->with($this->equalTo('token'))
			->willReturn($share);

		$share->method('getPermissions')
			->willReturn(Constants::PERMISSION_READ);

		$folder = $this->createMock(Folder::class);
		$share->method('getNode')
			->willReturn($folder);

		$folder->method('get')
			->with($this->equalTo('file'))
			->willThrowException(new NotFoundException());

		$res = $this->controller->getPreview('token', 'file', 10, 10, true);
		$expected = new DataResponse([], Http::STATUS_NOT_FOUND);
		$this->assertEquals($expected, $res);
	}


	public function testPreviewFolderValidFile(): void {
		$share = $this->createMock(IShare::class);
		$this->shareManager->method('getShareByToken')
			->with($this->equalTo('token'))
			->willReturn($share);

		$share->method('getPermissions')
			->willReturn(Constants::PERMISSION_READ);

		$folder = $this->createMock(Folder::class);
		$share->method('getNode')
			->willReturn($folder);

		$file = $this->createMock(File::class);
		$folder->method('get')
			->with($this->equalTo('file'))
			->willReturn($file);

		$preview = $this->createMock(ISimpleFile::class);
		$preview->method('getName')->willReturn('name');
		$preview->method('getMTime')->willReturn(42);
		$this->previewManager->method('getPreview')
			->with($this->equalTo($file), 10, 10, false)
			->willReturn($preview);

		$preview->method('getMimeType')
			->willReturn('myMime');

		$res = $this->controller->getPreview('token', 'file', 10, 10, true);
		$expected = new FileDisplayResponse($preview, Http::STATUS_OK, ['Content-Type' => 'myMime']);
		$expected->cacheFor(3600 * 24);
		$this->assertEquals($expected, $res);
	}
}
