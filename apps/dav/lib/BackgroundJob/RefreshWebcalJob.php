<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\BackgroundJob;

use DateInterval;
use OCA\DAV\CalDAV\WebcalCaching\RefreshWebcalService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
use OCP\BackgroundJob\Job;
use OCP\IConfig;
use Psr\Log\LoggerInterface;
use Sabre\VObject\DateTimeParser;
use Sabre\VObject\InvalidDataException;

class RefreshWebcalJob extends Job {
	public function __construct(
		private RefreshWebcalService $refreshWebcalService,
		private IConfig $config,
		private LoggerInterface $logger,
		ITimeFactory $timeFactory,
	) {
		parent::__construct($timeFactory);
	}

	/**
	 * this function is called at most every hour
	 *
	 * @inheritdoc
	 */
	public function start(IJobList $jobList): void {
		$subscription = $this->refreshWebcalService->getSubscription($this->argument['principaluri'], $this->argument['uri']);
		if (!$subscription) {
			return;
		}

		$this->fixSubscriptionRowTyping($subscription);

		// if no refresh rate was configured, just refresh once a day
		$defaultRefreshRate = $this->config->getAppValue('dav', 'calendarSubscriptionRefreshRate', 'P1D');
		$refreshRate = $subscription[RefreshWebcalService::REFRESH_RATE] ?? $defaultRefreshRate;

		$subscriptionId = $subscription['id'];

		try {
			/** @var DateInterval $dateInterval */
			$dateInterval = DateTimeParser::parseDuration($refreshRate);
		} catch (InvalidDataException $ex) {
			$this->logger->error(
				"Subscription $subscriptionId could not be refreshed, refreshrate in database is invalid",
				['exception' => $ex]
			);
			return;
		}

		$interval = $this->getIntervalFromDateInterval($dateInterval);
		if (($this->time->getTime() - $this->lastRun) <= $interval) {
			return;
		}

		parent::start($jobList);
	}

	/**
	 * @param array $argument
	 */
	protected function run($argument) {
		$this->refreshWebcalService->refreshSubscription($argument['principaluri'], $argument['uri']);
	}

	/**
	 * get total number of seconds from DateInterval object
	 *
	 * @param DateInterval $interval
	 * @return int
	 */
	private function getIntervalFromDateInterval(DateInterval $interval):int {
		return $interval->s
			+ ($interval->i * 60)
			+ ($interval->h * 60 * 60)
			+ ($interval->d * 60 * 60 * 24)
			+ ($interval->m * 60 * 60 * 24 * 30)
			+ ($interval->y * 60 * 60 * 24 * 365);
	}

	/**
	 * Fixes types of rows
	 *
	 * @param array $row
	 */
	private function fixSubscriptionRowTyping(array &$row):void {
		$forceInt = [
			'id',
			'lastmodified',
			RefreshWebcalService::STRIP_ALARMS,
			RefreshWebcalService::STRIP_ATTACHMENTS,
			RefreshWebcalService::STRIP_TODOS,
		];

		foreach ($forceInt as $column) {
			if (isset($row[$column])) {
				$row[$column] = (int)$row[$column];
			}
		}
	}
}
