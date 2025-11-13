<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\WebhookListeners\BackgroundJobs;

use OCA\WebhookListeners\Db\EphemeralTokenMapper;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;

class WebhookTokenCleanup extends TimedJob {

	public function __construct(
		private EphemeralTokenMapper $tokenMapper,
		ITimeFactory $timeFactory,
	) {
		parent::__construct($timeFactory);
		// every 5 min
		$this->setInterval(5 * 60);
	}

	/**
	 * @param array $argument
	 */
	protected function run($argument): void {
		$this->tokenMapper->invalidateOldTokens();
	}
}
