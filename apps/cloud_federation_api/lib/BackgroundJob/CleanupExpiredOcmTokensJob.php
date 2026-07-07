<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\CloudFederationAPI\BackgroundJob;

use OCA\CloudFederationAPI\Service\OcmTokenService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;

/**
 * Periodically purge expired OCM access tokens.
 *
 * Each expired mapping has its access token deleted from oc_authtoken before
 * its ocm_token_map row is removed. Dropping the mapping first would orphan
 * the access token, since nothing else records which oc_authtoken id belongs
 * to a given refresh token.
 */
class CleanupExpiredOcmTokensJob extends TimedJob {
	public function __construct(
		ITimeFactory $timeFactory,
		private readonly OcmTokenService $tokenService,
	) {
		parent::__construct($timeFactory);

		$this->setInterval(6 * 60 * 60); // run every 6 hours
		$this->setTimeSensitivity(self::TIME_INSENSITIVE);
	}

	#[\Override]
	protected function run($argument): void {
		$this->tokenService->revokeExpired($this->time->getTime());
	}
}
