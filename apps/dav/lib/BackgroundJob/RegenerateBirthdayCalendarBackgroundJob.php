<?php

declare(strict_types=1);

namespace OCA\DAV\BackgroundJob;

use OCA\DAV\CalDAV\BirthdayService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\QueuedJob;
use OCP\IConfig;

class RegenerateBirthdayCalendarBackgroundJob extends QueuedJob {
	public function __construct(
		ITimeFactory $time,
		private BirthdayService $birthdayService,
		private IConfig $config,
	) {
		parent::__construct($time);
	}

	#[\Override]
	protected function run($argument): void {
		$userId = $argument['userId'] ?? null;
		if (!is_string($userId) || $userId === '') {
			return;
		}

		/*
		 * Always purge first. This is important when the user has disabled
		 * birthday-calendar generation: stale generated events must still be
		 * removed.
		 */
		$this->birthdayService->resetForUser($userId);

		$isGloballyEnabled = $this->config->getAppValue(
			'dav',
			'generateBirthdayCalendar',
			'yes',
		);

		if ($isGloballyEnabled !== 'yes') {
			return;
		}

		$isUserEnabled = $this->config->getUserValue(
			$userId,
			'dav',
			'generateBirthdayCalendar',
			'yes',
		);

		if ($isUserEnabled !== 'yes') {
			return;
		}

		$this->birthdayService->syncUser($userId);
	}
}
