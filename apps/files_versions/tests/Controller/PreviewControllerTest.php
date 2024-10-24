<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_Versions\Tests\Controller;

use OCA\Files_Versions\Controller\PreviewController;
use OCA\Files_Versions\Versions\IVersionManager;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\FileDisplayResponse;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IMimeTypeDetector;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\Files\SimpleFS\ISimpleFile;
use OCP\IPreview;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use Test\TestCase;

class PreviewControllerTest extends TestCase {

	/** @var IRootFolder|\PHPUnit\Framework\MockObject\MockObject */
	private $rootFolder;

	/** @var string */
	private $userId;

	/** @var IMimeTypeDetector|\PHPUnit\Framework\MockObject\MockObject */
	private $mimeTypeDetector;

	/** @var IPreview|\PHPUnit\Framework\MockObject\MockObject */
	private $previewManager;

	/** @var PreviewController|\PHPUnit\Framework\MockObject\MockObject */
	private $controller;

	/** @var IUserSession|\PHPUnit\Framework\MockObject\MockObject */
	private $userSession;

	/** @var IVersionManager|\PHPUnit\Framework\MockObject\MockObject */
	private $versionManager;

	protected function setUp(): void {
		parent::setUp();

		$this->rootFolder = $this->createMock(IRootFolder::class);
		$this->userId = 'user';
		$user = $this->createMock(IUser::class);
		$user->expects($this->any())
			->method('getUID')
			->willReturn($this->userId);
		$this->previewManager = $this->createMock(IPreview::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->userSession->expects($this->any())
			->method('getUser')
			->willReturn($user);
		$this->versionManager = $this->createMock(IVersionManager::class);

		$this->controller = new PreviewController(
			'files_versions',
			$this->createMock(IRequest::class),
			$this->rootFolder,
			$this->userSession,
			$this->versionManager,
			$this->previewManager
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

	public function testInvalidVersion(): void {
		$res = $this->controller->getPreview('file', 10, 0);
		$expected = new DataResponse([], Http::STATUS_BAD_REQUEST);

		$this->assertEquals($expected, $res);
	}

	public function testValidPreview(): void {
		$userFolder = $this->createMock(Folder::class);
		$userRoot = $this->createMock(Folder::class);

		$this->rootFolder->method('getUserFolder')
			->with($this->userId)
			->willReturn($userFolder);
		$userFolder->method('getParent')
			->willReturn($userRoot);

		$sourceFile = $this->createMock(File::class);
		$userFolder->method('get')
			->with('file')
			->willReturn($sourceFile);

		$file = $this->createMock(File::class);
		$file->method('getMimetype')
			->willReturn('myMime');

		$this->versionManager->method('getVersionFile')
			->willReturn($file);

		$preview = $this->createMock(ISimpleFile::class);
		$preview->method('getName')->willReturn('name');
		$preview->method('getMTime')->willReturn(42);
		$this->previewManager->method('getPreview')
			->with($this->equalTo($file), 10, 10, true, IPreview::MODE_FILL, 'myMime')
			->willReturn($preview);
		$preview->method('getMimeType')
			->willReturn('previewMime');

		$res = $this->controller->getPreview('file', 10, 10, '42');
		$expected = new FileDisplayResponse($preview, Http::STATUS_OK, ['Content-Type' => 'previewMime']);

		$this->assertEquals($expected, $res);
	}

	public function testVersionNotFound(): void {
		$userFolder = $this->createMock(Folder::class);
		$userRoot = $this->createMock(Folder::class);

		$this->rootFolder->method('getUserFolder')
			->with($this->userId)
			->willReturn($userFolder);
		$userFolder->method('getParent')
			->willReturn($userRoot);

		$sourceFile = $this->createMock(File::class);
		$userFolder->method('get')
			->with('file')
			->willReturn($sourceFile);

		$this->versionManager->method('getVersionFile')
			->willThrowException(new NotFoundException());

		$res = $this->controller->getPreview('file', 10, 10, '42');
		$expected = new DataResponse([], Http::STATUS_NOT_FOUND);

		$this->assertEquals($expected, $res);
	}
}
