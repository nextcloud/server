<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Marcel Klehr <mklehr@gmx.net>
 *
 * @author Marcel Klehr <mklehr@gmx.net>
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
 */


namespace OC\TextToImage;

use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\QueuedJob;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\TextToImage\Events\TaskFailedEvent;
use OCP\TextToImage\Events\TaskSuccessfulEvent;
use OCP\TextToImage\IManager;

class TaskBackgroundJob extends QueuedJob {
	public function __construct(
		ITimeFactory $timeFactory,
		private IManager $text2imageManager,
		private IEventDispatcher $eventDispatcher,
	) {
		parent::__construct($timeFactory);
		// We want to avoid overloading the machine with these jobs
		// so we only allow running one job at a time
		$this->setAllowParallelRuns(false);
	}

	/**
	 * @param array{taskId: int} $argument
	 * @inheritDoc
	 */
	protected function run($argument) {
		$taskId = $argument['taskId'];
		$task = $this->text2imageManager->getTask($taskId);
		try {
			$this->text2imageManager->runTask($task);
			$event = new TaskSuccessfulEvent($task);
		} catch (\Throwable $e) {
			$event = new TaskFailedEvent($task, $e->getMessage());
		}
		$this->eventDispatcher->dispatchTyped($event);
	}
}
