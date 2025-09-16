<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Migration;

use OCP\BackgroundJob\IJobList;
use OCP\IAppConfig;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class BuildSocialSearchIndex implements IRepairStep {

	public function __construct(
		private readonly IDBConnection $db,
		private readonly IJobList $jobList,
		private readonly IAppConfig $config,
	) {
	}

	public function getName(): string {
		return 'Register building of social profile search index as background job';
	}

	/**
	 * @param IOutput $output
	 */
	public function run(IOutput $output) {
		// only run once
		if ($this->config->getValueBool('dav', 'builtSocialSearchIndex')) {
			$output->info('Repair step already executed');
			return;
		}

		$query = $this->db->getQueryBuilder();
		$query->select($query->func()->max('cardid'))
			->from('cards_properties')
			->where($query->expr()->eq('name', $query->createNamedParameter('X-SOCIALPROFILE')));
		$maxId = (int)$query->executeQuery()->fetchOne();

		if ($maxId === 0) {
			return;
		}

		$output->info('Add background job');
		$this->jobList->add(BuildSocialSearchIndexBackgroundJob::class, [
			'offset' => 0,
			'stopAt' => $maxId
		]);

		// no need to redo the repair during next upgrade
		$this->config->setValueBool('dav', 'builtSocialSearchIndex', true);
	}
}
