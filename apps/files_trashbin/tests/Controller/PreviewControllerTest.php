<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_Trashbin\Tests\Controller;

use OCA\Files_Trashbin\Controller\PreviewController;
use OCA\Files_Trashbin\Trash\ITrashManager;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\FileDisplayResponse;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IMimeTypeDetector;
use OCP\Files\IRootFolder;
use OCP\Files\SimpleFS\ISimpleFile;
use OCP\IPreview;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class PreviewControllerTest extends TestCase {
	private IRootFolder&MockObject $rootFolder;
	private string $userId;
	private IMimeTypeDetector&MockObject $mimeTypeDetector;
	private IPreview&MockObject $previewManager;
	private ITimeFactory&MockObject $time;
	private ITrashManager&MockObject $trashManager;
	private IUserSession&MockObject $userSession;
	private PreviewController $controller;

	protected function setUp(): void {
		parent::setUp();

		$this->rootFolder = $this->createMock(IRootFolder::class);
		$this->userId = 'user';
		$this->mimeTypeDetector = $this->createMock(IMimeTypeDetector::class);
		$this->previewManager = $this->createMock(IPreview::class);
		$this->time = $this->createMock(ITimeFactory::class);
		$this->trashManager = $this->createMock(ITrashManager::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$user = $this->createMock(IUser::class);
		$user->expects($this->any())
			->method('getUID')
			->willReturn($this->userId);

		$this->userSession->expects($this->any())
			->method('getUser')
			->willReturn($user);

		$this->controller = new PreviewController(
			'files_versions',
			$this->createMock(IRequest::class),
			$this->rootFolder,
			$this->trashManager,
			$this->userSession,
			$this->mimeTypeDetector,
			$this->previewManager,
			$this->time
		);
	}

	public function testInvalidWidth(): void {
		$res = $this->controller->getPreview(42, 0);
		$expected = new DataResponse([], Http::STATUS_BAD_REQUEST);

		$this->assertEquals($expected, $res);
	}

	public function testInvalidHeight(): void {
		$res = $this->controller->getPreview(42, 10, 0);
		$expected = new DataResponse([], Http::STATUS_BAD_REQUEST);

		$this->assertEquals($expected, $res);
	}

	public function testValidPreview(): void {
		$userFolder = $this->createMock(Folder::class);
		$userRoot = $this->createMock(Folder::class);
		$trash = $this->createMock(Folder::class);

		$this->rootFolder->method('getUserFolder')
			->with($this->userId)
			->willReturn($userFolder);
		$userFolder->method('getParent')
			->willReturn($userRoot);
		$userRoot->method('get')
			->with('files_trashbin/files')
			->willReturn($trash);

		$this->mimeTypeDetector->method('detectPath')
			->with($this->equalTo('file'))
			->willReturn('myMime');

		$file = $this->createMock(File::class);
		$trash->method('getById')
			->with($this->equalTo(42))
			->willReturn([$file]);
		$file->method('getName')
			->willReturn('file.d1234');

		$file->method('getParent')
			->willReturn($trash);

		$this->trashManager->expects($this->any())
			->method('getTrashNodeById')
			->willReturn($file);

		$preview = $this->createMock(ISimpleFile::class);
		$preview->method('getName')->willReturn('name');
		$preview->method('getMTime')->willReturn(42);
		$this->previewManager->method('getPreview')
			->with($this->equalTo($file), 10, 10, true, IPreview::MODE_FILL, 'myMime')
			->willReturn($preview);
		$preview->method('getMimeType')
			->willReturn('previewMime');

		$this->time->method('getTime')
			->willReturn(1337);

		$this->overwriteService(ITimeFactory::class, $this->time);

		$res = $this->controller->getPreview(42, 10, 10, false);
		$expected = new FileDisplayResponse($preview, Http::STATUS_OK, ['Content-Type' => 'previewMime']);
		$expected->cacheFor(3600 * 24);

		$this->assertEquals($expected, $res);
	}

	public function testTrashFileNotFound(): void {
		$userFolder = $this->createMock(Folder::class);
		$userRoot = $this->createMock(Folder::class);
		$trash = $this->createMock(Folder::class);

		$this->rootFolder->method('getUserFolder')
			->with($this->userId)
			->willReturn($userFolder);
		$userFolder->method('getParent')
			->willReturn($userRoot);
		$userRoot->method('get')
			->with('files_trashbin/files')
			->willReturn($trash);

		$trash->method('getById')
			->with($this->equalTo(42))
			->willReturn([]);

		$res = $this->controller->getPreview(42, 10, 10);
		$expected = new DataResponse([], Http::STATUS_NOT_FOUND);

		$this->assertEquals($expected, $res);
	}

	public function testTrashFolder(): void {
		$userFolder = $this->createMock(Folder::class);
		$userRoot = $this->createMock(Folder::class);
		$trash = $this->createMock(Folder::class);

		$this->rootFolder->method('getUserFolder')
			->with($this->userId)
			->willReturn($userFolder);
		$userFolder->method('getParent')
			->willReturn($userRoot);
		$userRoot->method('get')
			->with('files_trashbin/files')
			->willReturn($trash);

		$folder = $this->createMock(Folder::class);
		$this->trashManager->expects($this->any())
			->method('getTrashNodeById')
			->willReturn($folder);
		$trash->method('getById')
			->with($this->equalTo(43))
			->willReturn([$folder]);

		$res = $this->controller->getPreview(43, 10, 10);
		$expected = new DataResponse([], Http::STATUS_BAD_REQUEST);

		$this->assertEquals($expected, $res);
	}
}
