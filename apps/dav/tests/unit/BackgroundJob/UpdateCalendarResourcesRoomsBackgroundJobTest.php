<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2018, Georg Ehrke
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Georg Ehrke <oc.list@georgehrke.com>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
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
