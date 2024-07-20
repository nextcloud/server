<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Tests\unit\BackgroundJob;

use OCA\DAV\BackgroundJob\UpdateCalendarResourcesRoomsBackgroundJob;

use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Calendar\Resource\IManager as IResourceManager;
use OCP\Calendar\Room\IManager as IRoomManager;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class UpdateCalendarResourcesRoomsBackgroundJobTest extends TestCase {
	private UpdateCalendarResourcesRoomsBackgroundJob $backgroundJob;

	/** @var ITimeFactory|MockObject */
	private $time;

	/** @var IResourceManager|MockObject */
	private $resourceManager;

	/** @var IRoomManager|MockObject */
	private $roomManager;

	protected function setUp(): void {
		parent::setUp();

		$this->time = $this->createMock(ITimeFactory::class);
		$this->resourceManager = $this->createMock(IResourceManager::class);
		$this->roomManager = $this->createMock(IRoomManager::class);

		$this->backgroundJob = new UpdateCalendarResourcesRoomsBackgroundJob(
			$this->time,
			$this->resourceManager,
			$this->roomManager,
		);
	}

	public function testRun(): void {
		$this->resourceManager->expects(self::once())
			->method('update');
		$this->roomManager->expects(self::once())
			->method('update');

		$this->backgroundJob->run([]);
	}
}
