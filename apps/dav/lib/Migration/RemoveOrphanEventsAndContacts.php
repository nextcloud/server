<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Migration;

use OCA\DAV\BackgroundJob\CleanupOrphanedChildrenJob;
use OCP\BackgroundJob\IJobList;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class RemoveOrphanEventsAndContacts implements IRepairStep {
	public function __construct(
		private readonly IJobList $jobList,
	) {
	}

	public function getName(): string {
		return 'Queue jobs to clean up orphan event and contact data';
	}

	public function run(IOutput $output): void {
		$this->queueJob('calendarobjects', 'calendars', 'calendarid', '%d events without a calendar have been cleaned up');
		$this->queueJob('calendarobjects_props', 'calendarobjects', 'objectid', '%d properties without an events have been cleaned up');
		$this->queueJob('calendarchanges', 'calendars', 'calendarid', '%d changes without a calendar have been cleaned up');

		$this->queueJob('calendarobjects', 'calendarsubscriptions', 'calendarid', '%d cached events without a calendar subscription have been cleaned up');
		$this->queueJob('calendarchanges', 'calendarsubscriptions', 'calendarid', '%d changes without a calendar subscription have been cleaned up');

		$this->queueJob('cards', 'addressbooks', 'addressbookid', '%d contacts without an addressbook have been cleaned up');
		$this->queueJob('cards_properties', 'cards', 'cardid', '%d properties without a contact have been cleaned up');
		$this->queueJob('addressbookchanges', 'addressbooks', 'addressbookid', '%d changes without an addressbook have been cleaned up');
	}

	private function queueJob(
		string $childTable,
		string $parentTable,
		string $parentId,
		string $logMessage,
	): void {
		$this->jobList->add(CleanupOrphanedChildrenJob::class, [
			CleanupOrphanedChildrenJob::ARGUMENT_CHILD_TABLE => $childTable,
			CleanupOrphanedChildrenJob::ARGUMENT_PARENT_TABLE => $parentTable,
			CleanupOrphanedChildrenJob::ARGUMENT_PARENT_ID => $parentId,
			CleanupOrphanedChildrenJob::ARGUMENT_LOG_MESSAGE => $logMessage,
		]);
	}
}
