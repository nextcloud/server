<?php

/**
 * SPDX-FileCopyrightText: 2016 ownCloud GmbH.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Migration;

use OCA\DAV\CalDAV\BirthdayService;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class FixBirthdayCalendarComponent implements IRepairStep {

	public function __construct(
		private IDBConnection $connection,
	) {
	}

	/**
	 * @inheritdoc
	 */
	public function getName() {
		return 'Fix component of birthday calendars';
	}

	/**
	 * @inheritdoc
	 */
	public function run(IOutput $output) {
		$query = $this->connection->getQueryBuilder();
		$updated = $query->update('calendars')
			->set('components', $query->createNamedParameter('VEVENT'))
			->where($query->expr()->eq('uri', $query->createNamedParameter(BirthdayService::BIRTHDAY_CALENDAR_URI)))
			->executeStatement();

		$output->info("$updated birthday calendars updated.");
	}
}
