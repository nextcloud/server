<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Migration;

use OCA\DAV\BackgroundJob\RefreshWebcalJob;
use OCP\BackgroundJob\IJobList;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class RefreshWebcalJobRegistrar implements IRepairStep {

	/**
	 * FixBirthdayCalendarComponent constructor.
	 *
	 * @param IDBConnection $connection
	 * @param IJobList $jobList
	 */
	public function __construct(
		private IDBConnection $connection,
		private IJobList $jobList,
	) {
	}

	/**
	 * @inheritdoc
	 */
	public function getName() {
		return 'Registering background jobs to update cache for webcal calendars';
	}

	/**
	 * @inheritdoc
	 */
	public function run(IOutput $output) {
		$query = $this->connection->getQueryBuilder();
		$query->select(['principaluri', 'uri'])
			->from('calendarsubscriptions');
		$stmt = $query->execute();

		$count = 0;
		while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
			$args = [
				'principaluri' => $row['principaluri'],
				'uri' => $row['uri'],
			];

			if (!$this->jobList->has(RefreshWebcalJob::class, $args)) {
				$this->jobList->add(RefreshWebcalJob::class, $args);
				$count++;
			}
		}

		$output->info("Added $count background jobs to update webcal calendars");
	}
}
