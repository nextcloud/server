<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\DAV\Security;

use OCA\DAV\Connector\Sabre\Exception\TooManyRequests;
use OCP\IAppConfig;
use OCP\IUserSession;
use OCP\Security\RateLimiting\ILimiter;
use OCP\Security\RateLimiting\IRateLimitExceededException;

class RateLimiting {

	public function __construct(
		private readonly IUserSession $userSession,
		private readonly IAppConfig $config,
		private readonly ILimiter $limiter,
	) {
	}

	/**
	 * @throws TooManyRequests
	 */
	public function check(): void {
		$user = $this->userSession->getUser();
		if ($user === null) {
			return;
		}

		$identifier = 'share-addressbook-or-calendar';
		$userLimit = $this->config->getValueInt('dav', 'rateLimitShareAddressbookOrCalendar', 20);
		$userPeriod = $this->config->getValueInt('dav', 'rateLimitPeriodShareAddressbookOrCalendar', 3600);

		try {
			$this->limiter->registerUserRequest($identifier, $userLimit, $userPeriod, $user);
		} catch (IRateLimitExceededException $e) {
			throw new TooManyRequests('Too many addressbook or calendar share requests', 0, $e);
		}
	}
}
