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

use OC\TextToImage\Db\TaskMapper;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use OCP\DB\Exception;
use OCP\Files\AppData\IAppDataFactory;
use OCP\Files\IAppData;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use Psr\Log\LoggerInterface;

class RemoveOldTasksBackgroundJob extends TimedJob {
	public const MAX_TASK_AGE_SECONDS = 60 * 50 * 24 * 7; // 1 week

	private IAppData $appData;

	public function __construct(
		ITimeFactory $timeFactory,
		private TaskMapper $taskMapper,
		private LoggerInterface $logger,
		private IAppDataFactory $appDataFactory,
	) {
		parent::__construct($timeFactory);
		$this->appData = $this->appDataFactory->get('core');
		$this->setInterval(60 * 60 * 24);
	}

	/**
	 * @param mixed $argument
	 * @inheritDoc
	 */
	protected function run($argument) {
		try {
			$deletedTasks = $this->taskMapper->deleteOlderThan(self::MAX_TASK_AGE_SECONDS);
			$folder = $this->appData->getFolder('text2image');
			foreach ($deletedTasks as $deletedTask) {
				try {
					$folder->getFile((string)$deletedTask->getId())->delete();
				} catch (NotFoundException) {
					// noop
				} catch (NotPermittedException $e) {
					$this->logger->warning('Failed to delete stale text to image task', ['exception' => $e]);
				}
			}
		} catch (Exception $e) {
			$this->logger->warning('Failed to delete stale text to image tasks', ['exception' => $e]);
		} catch(NotFoundException) {
			// noop
		}
	}
}
