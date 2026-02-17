<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\Controller;

use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\Files\IAppData;
use OCP\Files\IMimeTypeDetector;
use OCP\Files\IRootFolder;
use OCP\IL10N;
use OCP\IRequest;
use OCP\TaskProcessing\Exception\Exception;
use OCP\TaskProcessing\IManager;
use OCP\TaskProcessing\Task;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class TaskProcessingApiControllerTest extends TestCase {
	private IRequest&MockObject $request;
	private IManager&MockObject $taskProcessingManager;
	private IL10N&MockObject $l;
	private IRootFolder&MockObject $rootFolder;
	private IAppData&MockObject $appData;
	private IMimeTypeDetector&MockObject $mimeTypeDetector;
	private TaskProcessingApiController $controller;

	protected function setUp(): void {
		parent::setUp();

		$this->request = $this->createMock(IRequest::class);
		$this->taskProcessingManager = $this->createMock(IManager::class);
		$this->l = $this->createMock(IL10N::class);
		$this->l->method('t')->willReturnCallback(fn (string $text) => $text);
		$this->rootFolder = $this->createMock(IRootFolder::class);
		$this->appData = $this->createMock(IAppData::class);
		$this->mimeTypeDetector = $this->createMock(IMimeTypeDetector::class);

		$this->controller = new TaskProcessingApiController(
			'core',
			$this->request,
			$this->taskProcessingManager,
			$this->l,
			'testuser',
			$this->rootFolder,
			$this->appData,
			$this->mimeTypeDetector,
		);
	}

	public function testQueueStatsNoFilter(): void {
		$this->taskProcessingManager->expects($this->exactly(2))
			->method('countTasks')
			->willReturnMap([
				[Task::STATUS_SCHEDULED, [], 5],
				[Task::STATUS_RUNNING, [], 3],
			]);

		$response = $this->controller->queueStats();

		$this->assertEquals(Http::STATUS_OK, $response->getStatus());
		$this->assertEquals(['scheduled' => 5, 'running' => 3], $response->getData());
	}

	public function testQueueStatsWithTaskTypeFilter(): void {
		$taskTypeIds = ['core:text2text', 'core:text2image'];

		$this->taskProcessingManager->expects($this->exactly(2))
			->method('countTasks')
			->willReturnMap([
				[Task::STATUS_SCHEDULED, $taskTypeIds, 2],
				[Task::STATUS_RUNNING, $taskTypeIds, 1],
			]);

		$response = $this->controller->queueStats($taskTypeIds);

		$this->assertEquals(Http::STATUS_OK, $response->getStatus());
		$this->assertEquals(['scheduled' => 2, 'running' => 1], $response->getData());
	}

	public function testQueueStatsWithSingleTaskType(): void {
		$taskTypeIds = ['core:text2text'];

		$this->taskProcessingManager->expects($this->exactly(2))
			->method('countTasks')
			->willReturnMap([
				[Task::STATUS_SCHEDULED, $taskTypeIds, 10],
				[Task::STATUS_RUNNING, $taskTypeIds, 0],
			]);

		$response = $this->controller->queueStats($taskTypeIds);

		$this->assertEquals(Http::STATUS_OK, $response->getStatus());
		$this->assertEquals(['scheduled' => 10, 'running' => 0], $response->getData());
	}

	public function testQueueStatsEmptyQueue(): void {
		$this->taskProcessingManager->expects($this->exactly(2))
			->method('countTasks')
			->willReturnMap([
				[Task::STATUS_SCHEDULED, [], 0],
				[Task::STATUS_RUNNING, [], 0],
			]);

		$response = $this->controller->queueStats();

		$this->assertEquals(Http::STATUS_OK, $response->getStatus());
		$this->assertEquals(['scheduled' => 0, 'running' => 0], $response->getData());
	}

	public function testQueueStatsManagerException(): void {
		$this->taskProcessingManager->expects($this->once())
			->method('countTasks')
			->willThrowException(new Exception('DB error'));

		$response = $this->controller->queueStats();

		$this->assertEquals(Http::STATUS_INTERNAL_SERVER_ERROR, $response->getStatus());
		$this->assertEquals(['message' => 'Internal error'], $response->getData());
	}
}
