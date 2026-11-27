<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Tests\Core\Controller;

use OC\Core\Controller\TaskProcessingApiController;
use OC\User\NoUserException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\StreamResponse;
use OCP\Files\File;
use OCP\Files\IAppData;
use OCP\Files\IMimeTypeDetector;
use OCP\Files\IRootFolder;
use OCP\Files\IUserFolder;
use OCP\IL10N;
use OCP\IRequest;
use OCP\TaskProcessing\IManager;
use OCP\TaskProcessing\Task;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

#[Group('DB')]
class TaskProcessingApiControllerTest extends TestCase {
	private const USER_ID = 'task-processing-user';
	private const TASK_ID = 1;
	private const FILE_ID = 42;

	private IManager&MockObject $taskProcessingManager;
	private IRootFolder&MockObject $rootFolder;
	private IUserFolder&MockObject $userFolder;
	private Task $task;
	private TaskProcessingApiController $controller;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->task = new Task('ocr', ['input' => [self::FILE_ID]], 'test', self::USER_ID);

		$this->taskProcessingManager = $this->createMock(IManager::class);
		$this->taskProcessingManager->method('getUserTask')
			->with(self::TASK_ID, self::USER_ID)
			->willReturn($this->task);
		$this->taskProcessingManager->method('getTask')
			->with(self::TASK_ID)
			->willReturn($this->task);
		$this->taskProcessingManager->method('extractFileIdsFromTask')
			->with($this->task)
			->willReturn([self::FILE_ID]);

		$this->userFolder = $this->createMock(IUserFolder::class);
		$this->rootFolder = $this->createMock(IRootFolder::class);
		$this->rootFolder->method('getAppDataDirectoryName')
			->willReturn('appdata_test');
		// The root folder would also find nodes in the files of other users
		$this->rootFolder->expects($this->never())
			->method('getFirstNodeById');

		$l = $this->createMock(IL10N::class);
		$l->method('t')
			->willReturnArgument(0);
		$mimeTypeDetector = $this->createMock(IMimeTypeDetector::class);
		$mimeTypeDetector->method('getSecureMimeType')
			->willReturnArgument(0);

		$this->controller = new TaskProcessingApiController(
			'core',
			$this->createMock(IRequest::class),
			$this->taskProcessingManager,
			$l,
			self::USER_ID,
			$this->rootFolder,
			$this->createMock(IAppData::class),
			$mimeTypeDetector,
		);
	}

	private function createFile(): File&MockObject {
		$file = $this->createMock(File::class);
		$file->method('getName')
			->willReturn('scan.txt');
		$file->method('getMimeType')
			->willReturn('text/plain');
		$file->method('fopen')
			->willReturnCallback(static function () {
				$stream = fopen('php://memory', 'r+b');
				fwrite($stream, 'content');
				rewind($stream);
				return $stream;
			});
		return $file;
	}

	public static function dataRoutes(): array {
		return [
			['getFileContents'],
			['getFileContentsExApp'],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataRoutes')]
	public function testGetFileContentsOfFileInUserFolder(string $route): void {
		$this->rootFolder->expects($this->once())
			->method('getUserFolder')
			->with(self::USER_ID)
			->willReturn($this->userFolder);
		$this->userFolder->expects($this->once())
			->method('getFirstNodeById')
			->with(self::FILE_ID)
			->willReturn($this->createFile());
		$this->rootFolder->expects($this->never())
			->method('getFirstNodeByIdInPath');

		$response = $this->controller->$route(self::TASK_ID, self::FILE_ID);

		$this->assertInstanceOf(StreamResponse::class, $response);
		$this->assertSame(Http::STATUS_OK, $response->getStatus());
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataRoutes')]
	public function testGetFileContentsOfFileTheUserHasNoAccessTo(string $route): void {
		$this->rootFolder->expects($this->once())
			->method('getUserFolder')
			->with(self::USER_ID)
			->willReturn($this->userFolder);
		$this->userFolder->expects($this->once())
			->method('getFirstNodeById')
			->with(self::FILE_ID)
			->willReturn(null);
		$this->rootFolder->expects($this->once())
			->method('getFirstNodeByIdInPath')
			->with(self::FILE_ID, '/appdata_test/')
			->willReturn(null);

		$response = $this->controller->$route(self::TASK_ID, self::FILE_ID);

		$this->assertInstanceOf(DataResponse::class, $response);
		$this->assertSame(Http::STATUS_NOT_FOUND, $response->getStatus());
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataRoutes')]
	public function testGetFileContentsOfFileInAppData(string $route): void {
		$this->rootFolder->expects($this->once())
			->method('getUserFolder')
			->with(self::USER_ID)
			->willReturn($this->userFolder);
		$this->userFolder->expects($this->once())
			->method('getFirstNodeById')
			->with(self::FILE_ID)
			->willReturn(null);
		$this->rootFolder->expects($this->once())
			->method('getFirstNodeByIdInPath')
			->with(self::FILE_ID, '/appdata_test/')
			->willReturn($this->createFile());

		$response = $this->controller->$route(self::TASK_ID, self::FILE_ID);

		$this->assertInstanceOf(StreamResponse::class, $response);
		$this->assertSame(Http::STATUS_OK, $response->getStatus());
	}

	public function testGetFileContentsExAppOfFileInAppDataAfterUserWasDeleted(): void {
		$this->rootFolder->expects($this->once())
			->method('getUserFolder')
			->with(self::USER_ID)
			->willThrowException(new NoUserException());
		$this->rootFolder->expects($this->once())
			->method('getFirstNodeByIdInPath')
			->with(self::FILE_ID, '/appdata_test/')
			->willReturn($this->createFile());

		$response = $this->controller->getFileContentsExApp(self::TASK_ID, self::FILE_ID);

		$this->assertInstanceOf(StreamResponse::class, $response);
		$this->assertSame(Http::STATUS_OK, $response->getStatus());
	}
}
