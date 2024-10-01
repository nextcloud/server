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

	/** @var IDBConnection */
	private $connection;

	/**
	 * FixBirthdayCalendarComponent constructor.
	 *
	 * @param IDBConnection $connection
	 */
	public function __construct(IDBConnection $connection) {
		$this->connection = $connection;
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
			->execute();

		$output->info("$updated birthday calendars updated.");
	}
}
