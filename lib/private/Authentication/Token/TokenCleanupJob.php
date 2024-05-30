<?php
/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Authentication\Token;

use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;

class TokenCleanupJob extends TimedJob {
	private IProvider $provider;

	public function __construct(ITimeFactory $time, IProvider $provider) {
		parent::__construct($time);
		$this->provider = $provider;
		// Run once a day at off-peak time
		$this->setInterval(24 * 60 * 60);
		$this->setTimeSensitivity(self::TIME_INSENSITIVE);
	}

	protected function run($argument) {
		$this->provider->invalidateOldTokens();
	}
}
