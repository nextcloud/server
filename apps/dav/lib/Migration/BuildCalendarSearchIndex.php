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

	/** @var IDBConnection */
	private $db;

	/** @var IJobList */
	private $jobList;

	/** @var IConfig */
	private $config;

	/**
	 * @param IDBConnection $db
	 * @param IJobList $jobList
	 * @param IConfig $config
	 */
	public function __construct(IDBConnection $db,
		IJobList $jobList,
		IConfig $config) {
		$this->db = $db;
		$this->jobList = $jobList;
		$this->config = $config;
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
		$result = $query->execute();
		$maxId = (int) $result->fetchOne();
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
