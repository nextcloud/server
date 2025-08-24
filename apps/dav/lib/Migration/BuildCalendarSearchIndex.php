<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Migration;

use OCP\BackgroundJob\IJobList;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class BuildCalendarSearchIndex implements IRepairStep {

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
		return 'Registering building of calendar search index as background job';
	}

	/**
	 * @param IOutput $output
	 */
	public function run(IOutput $output) {
		// only run once
		if ($this->config->getAppValue('dav', 'buildCalendarSearchIndex') === 'yes') {
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
		$this->jobList->add(BuildCalendarSearchIndexBackgroundJob::class, [
			'offset' => 0,
			'stopAt' => $maxId
		]);

		// if all were done, no need to redo the repair during next upgrade
		$this->config->setAppValue('dav', 'buildCalendarSearchIndex', 'yes');
	}
}
