<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Migration;

use OCA\DAV\BackgroundJob\BuildReminderIndexBackgroundJob;
use OCP\BackgroundJob\IJobList;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

/**
 * Class RegisterBuildReminderIndexBackgroundJob
 *
 * @package OCA\DAV\Migration
 */
class RegisterBuildReminderIndexBackgroundJob implements IRepairStep {

	/** @var string */
	private const CONFIG_KEY = 'buildCalendarReminderIndex';

	/**
	 * @param IDBConnection $db
	 * @param IJobList $jobList
	 * @param IConfig $config
	 */
	public function __construct(
		private IDBConnection $db,
		private IJobList $jobList,
		private IConfig $config,
	) {
	}

	/**
	 * @return string
	 */
	public function getName() {
		return 'Registering building of calendar reminder index as background job';
	}

	/**
	 * @param IOutput $output
	 */
	public function run(IOutput $output) {
		// only run once
		if ($this->config->getAppValue('dav', self::CONFIG_KEY) === 'yes') {
			$output->info('Repair step already executed');
			return;
		}

		$query = $this->db->getQueryBuilder();
		$query->select($query->createFunction('MAX(' . $query->getColumnName('id') . ')'))
			->from('calendarobjects');
		$result = $query->executeQuery();
		$maxId = (int)$result->fetchOne();
		$result->closeCursor();

		$output->info('Add background job');
		$this->jobList->add(BuildReminderIndexBackgroundJob::class, [
			'offset' => 0,
			'stopAt' => $maxId
		]);

		// if all were done, no need to redo the repair during next upgrade
		$this->config->setAppValue('dav', self::CONFIG_KEY, 'yes');
	}
}
