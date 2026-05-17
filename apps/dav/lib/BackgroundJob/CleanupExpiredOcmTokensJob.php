<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\BackgroundJob;

use OCA\DAV\Db\OcmTokenMapMapper;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;

/**
 * Periodically purge expired OCM access token mappings from dav_ocm_token_map.
 *
 * The corresponding oc_authtoken entries (TEMPORARY_TOKEN with an expires
 * timestamp) are cleaned up by Nextcloud's own token expiry jobs.
 */
class CleanupExpiredOcmTokensJob extends TimedJob {
	public function __construct(
		ITimeFactory $timeFactory,
		private readonly OcmTokenMapMapper $mapper,
	) {
		parent::__construct($timeFactory);

		$this->setInterval(6 * 60 * 60); // run every 6 hours
		$this->setTimeSensitivity(self::TIME_INSENSITIVE);
	}

	#[\Override]
	protected function run($argument): void {
		$this->mapper->deleteExpired($this->time->getTime());
	}
}
