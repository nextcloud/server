<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\BackgroundJob;

use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use OCP\IDBConnection;

class CleanupInvitationTokenJob extends TimedJob {

	public function __construct(
		private IDBConnection $db,
		ITimeFactory $time,
	) {
		parent::__construct($time);

		// Run once a day at off-peak time
		$this->setInterval(24 * 60 * 60);
		$this->setTimeSensitivity(self::TIME_INSENSITIVE);
	}

	public function run($argument) {
		$query = $this->db->getQueryBuilder();
		$query->delete('calendar_invitations')
			->where($query->expr()->lt('expiration',
				$query->createNamedParameter($this->time->getTime())))
			->execute();
	}
}
