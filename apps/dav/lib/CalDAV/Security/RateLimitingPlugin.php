<?php

declare(strict_types=1);

/*
 * @copyright 2023 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2023 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace OCA\DAV\CalDAV\Security;

use OC\Security\RateLimiting\Exception\RateLimitExceededException;
use OC\Security\RateLimiting\Limiter;
use OCA\DAV\CalDAV\CalDavBackend;
use OCA\DAV\Connector\Sabre\Exception\TooManyRequests;
use OCP\IConfig;
use OCP\IUserManager;
use Psr\Log\LoggerInterface;
use Sabre\DAV;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\ServerPlugin;
use function count;
use function explode;

class RateLimitingPlugin extends ServerPlugin {

	private Limiter $limiter;
	private IUserManager $userManager;
	private CalDavBackend $calDavBackend;
	private IConfig $config;
	private LoggerInterface $logger;
	private ?string $userId;

	public function __construct(Limiter $limiter,
		IUserManager $userManager,
		CalDavBackend $calDavBackend,
		LoggerInterface $logger,
		IConfig $config,
		?string $userId) {
		$this->limiter = $limiter;
		$this->userManager = $userManager;
		$this->calDavBackend = $calDavBackend;
		$this->config = $config;
		$this->logger = $logger;
		$this->userId = $userId;
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
					(int) $this->config->getAppValue('dav', 'rateLimitCalendarCreation', '10'),
					(int) $this->config->getAppValue('dav', 'rateLimitPeriodCalendarCreation', '3600'),
					$user
				);
			} catch (RateLimitExceededException $e) {
				throw new TooManyRequests('Too many calendars created', 0, $e);
			}

			$calendarLimit = (int) $this->config->getAppValue('dav', 'maximumCalendarsSubscriptions', '30');
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
