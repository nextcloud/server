<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @author Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\DAV\BackgroundJob;

use OCA\DAV\CalDAV\TimezoneService;
use OCA\DAV\Db\AbsenceMapper;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\QueuedJob;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IUserManager;
use OCP\User\Events\OutOfOfficeEndedEvent;
use OCP\User\Events\OutOfOfficeStartedEvent;
use Psr\Log\LoggerInterface;

class OutOfOfficeEventDispatcherJob extends QueuedJob {
	public const EVENT_START = 'start';
	public const EVENT_END = 'end';

	public function __construct(
		ITimeFactory $time,
		private AbsenceMapper $absenceMapper,
		private LoggerInterface $logger,
		private IEventDispatcher $eventDispatcher,
		private IUserManager $userManager,
		private TimezoneService $timezoneService,
	) {
		parent::__construct($time);
	}

	public function run($argument): void {
		$id = $argument['id'];
		$event = $argument['event'];

		try {
			$absence = $this->absenceMapper->findById($id);
		} catch (DoesNotExistException | \OCP\DB\Exception $e) {
			$this->logger->error('Failed to dispatch out-of-office event: ' . $e->getMessage(), [
				'exception' => $e,
				'argument' => $argument,
			]);
			return;
		}

		$userId = $absence->getUserId();
		$user = $this->userManager->get($userId);
		if ($user === null) {
			$this->logger->error("Failed to dispatch out-of-office event: User $userId does not exist", [
				'argument' => $argument,
			]);
			return;
		}

		$data = $absence->toOutOufOfficeData(
			$user,
			$this->timezoneService->getUserTimezone($userId) ?? $this->timezoneService->getDefaultTimezone(),
		);
		if ($event === self::EVENT_START) {
			$this->eventDispatcher->dispatchTyped(new OutOfOfficeStartedEvent($data));
		} elseif ($event === self::EVENT_END) {
			$this->eventDispatcher->dispatchTyped(new OutOfOfficeEndedEvent($data));
		} else {
			$this->logger->error("Invalid out-of-office event: $event", [
				'argument' => $argument,
			]);
		}
	}
}
