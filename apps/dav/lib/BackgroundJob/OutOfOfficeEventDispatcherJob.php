<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
		} catch (DoesNotExistException|\OCP\DB\Exception $e) {
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
