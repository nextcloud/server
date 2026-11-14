<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Migration;

use Closure;
use OCP\BackgroundJob\IJobList;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
use Override;

/**
 * Index the CONFERENCE property of calendar objects stored before it became searchable
 */
class Version3000Date20261112000000 extends SimpleMigrationStep {
	public function __construct(
		private readonly IDBConnection $db,
		private readonly IJobList $jobList,
	) {
	}

	#[Override]
	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
		$query = $this->db->getQueryBuilder();
		$query->select($query->func()->max('id'))
			->from('calendarobjects');
		$result = $query->executeQuery();
		$maxId = (int)$result->fetchOne();
		$result->closeCursor();

		$this->jobList->add(BuildCalendarSearchIndexBackgroundJob::class, [
			'offset' => 0,
			'stopAt' => $maxId,
			'property' => 'CONFERENCE',
		]);
	}
}
