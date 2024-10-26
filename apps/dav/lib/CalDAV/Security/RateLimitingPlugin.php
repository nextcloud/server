<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\CalDAV\Security;

use OC\Security\RateLimiting\Exception\RateLimitExceededException;
use OC\Security\RateLimiting\Limiter;
use OCA\DAV\CalDAV\CalDavBackend;
use OCA\DAV\Connector\Sabre\Exception\TooManyRequests;
use OCP\IAppConfig;
use OCP\IUserManager;
use Psr\Log\LoggerInterface;
use Sabre\DAV;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\ServerPlugin;
use function count;
use function explode;

class RateLimitingPlugin extends ServerPlugin {

	private Limiter $limiter;

	public function __construct(
		Limiter $limiter,
		private IUserManager $userManager,
		private CalDavBackend $calDavBackend,
		private LoggerInterface $logger,
		private IAppConfig $config,
		private ?string $userId,
	) {
		$this->limiter = $limiter;
	}

	public function initialize(DAV\Server $server): void {
		$server->on('beforeBind', [$this, 'beforeBind'], 1);
	}

	public function beforeBind(string $path): void {
		if ($this->userId === null) {
			// We only care about authenticated users here
			return;
		}
		$user = $this->userManager->get($this->userId);
		if ($user === null) {
			// We only care about authenticated users here
			return;
		}

		$pathParts = explode('/', $path);
		if (count($pathParts) === 3 && $pathParts[0] === 'calendars') {
			// Path looks like calendars/username/calendarname so a new calendar or subscription is created
			try {
				$this->limiter->registerUserRequest(
					'caldav-create-calendar',
					$this->config->getValueInt('dav', 'rateLimitCalendarCreation', 10),
					$this->config->getValueInt('dav', 'rateLimitPeriodCalendarCreation', 3600),
					$user
				);
			} catch (RateLimitExceededException $e) {
				throw new TooManyRequests('Too many calendars created', 0, $e);
			}

			$calendarLimit = $this->config->getValueInt('dav', 'maximumCalendarsSubscriptions', 30);
			if ($calendarLimit === -1) {
				return;
			}
			$numCalendars = $this->calDavBackend->getCalendarsForUserCount('principals/users/' . $user->getUID());
			$numSubscriptions = $this->calDavBackend->getSubscriptionsForUserCount('principals/users/' . $user->getUID());

			if (($numCalendars + $numSubscriptions) >= $calendarLimit) {
				$this->logger->warning('Maximum number of calendars/subscriptions reached', [
					'calendars' => $numCalendars,
					'subscription' => $numSubscriptions,
					'limit' => $calendarLimit,
				]);
				throw new Forbidden('Calendar limit reached', 0);
			}
		}
	}

}
