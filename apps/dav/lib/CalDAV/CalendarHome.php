<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Georg Ehrke <oc.list@georgehrke.com>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\DAV\CalDAV;

use OCA\DAV\AppInfo\PluginManager;
use OCA\DAV\CalDAV\Integration\ExternalCalendar;
use OCA\DAV\CalDAV\Integration\ICalendarProvider;
use OCA\DAV\CalDAV\Trashbin\TrashbinHome;
use Psr\Log\LoggerInterface;
use OCP\App\IAppManager;
use OCP\IConfig;
use OCP\IL10N;
use OCP\L10N\IFactory;
use Sabre\CalDAV\Backend\BackendInterface;
use Sabre\CalDAV\Backend\NotificationSupport;
use Sabre\CalDAV\Backend\SchedulingSupport;
use Sabre\CalDAV\Backend\SubscriptionSupport;
use Sabre\CalDAV\Notifications\Collection;
use Sabre\CalDAV\Schedule\Inbox;
use Sabre\CalDAV\Subscriptions\Subscription;
use Sabre\DAV\Exception\MethodNotAllowed;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\INode;
use Sabre\DAV\MkCol;

class CalendarHome extends \Sabre\CalDAV\CalendarHome {
	private IL10N $l10n;
	private IConfig $config;
	private PluginManager $pluginManager;
	private bool $returnCachedSubscriptions = false;

	/** @var LoggerInterface */
	private $logger;

	public function __construct(BackendInterface $caldavBackend, $principalInfo, LoggerInterface $logger) {
		parent::__construct($caldavBackend, $principalInfo);
		$this->l10n = \OC::$server->get(IFactory::class)->get('dav');
		$this->config = \OC::$server->get(IConfig::class);
		$this->pluginManager = new PluginManager(
			\OC::$server,
			\OC::$server->get(IAppManager::class)
		);
		$this->logger = $logger;
	}

	public function getCalDAVBackend(): BackendInterface {
		return $this->caldavBackend;
	}

	/**
	 * @inheritdoc
	 */
	public function createExtendedCollection($name, MkCol $mkCol): void {
		$reservedNames = [
			BirthdayService::BIRTHDAY_CALENDAR_URI,
			TrashbinHome::NAME,
		];

		if (\in_array($name, $reservedNames, true) || ExternalCalendar::doesViolateReservedName($name)) {
			throw new MethodNotAllowed('The resource you tried to create has a reserved name');
		}

		parent::createExtendedCollection($name, $mkCol);
	}

	/**
	 * @inheritdoc
	 */
	public function getChildren() {
		$calendars = $this->caldavBackend->getCalendarsForUser($this->principalInfo['uri']);
		$objects = [];
		foreach ($calendars as $calendar) {
			$objects[] = new Calendar($this->caldavBackend, $calendar, $this->l10n, $this->config, $this->logger);
		}

		if ($this->caldavBackend instanceof SchedulingSupport) {
			$objects[] = new Inbox($this->caldavBackend, $this->principalInfo['uri']);
			$objects[] = new Outbox($this->config, $this->principalInfo['uri']);
		}

		// We're adding a notifications node, if it's supported by the backend.
		if ($this->caldavBackend instanceof NotificationSupport) {
			$objects[] = new Collection($this->caldavBackend, $this->principalInfo['uri']);
		}

		if ($this->caldavBackend instanceof CalDavBackend) {
			$objects[] = new TrashbinHome($this->caldavBackend, $this->principalInfo);
		}

		// If the backend supports subscriptions, we'll add those as well,
		if ($this->caldavBackend instanceof SubscriptionSupport) {
			foreach ($this->caldavBackend->getSubscriptionsForUser($this->principalInfo['uri']) as $subscription) {
				if ($this->returnCachedSubscriptions) {
					$objects[] = new CachedSubscription($this->caldavBackend, $subscription);
				} else {
					$objects[] = new Subscription($this->caldavBackend, $subscription);
				}
			}
		}

		foreach ($this->pluginManager->getCalendarPlugins() as $calendarPlugin) {
			$calendars = $calendarPlugin->fetchAllForCalendarHome($this->principalInfo['uri']);
			foreach ($calendars as $calendar) {
				$objects[] = $calendar;
			}
		}

		return $objects;
	}

	/**
	 * @param string $name
	 *
	 * @return INode
	 */
	public function getChild($name) {
		// Special nodes
		if ($name === 'inbox' && $this->caldavBackend instanceof SchedulingSupport) {
			return new Inbox($this->caldavBackend, $this->principalInfo['uri']);
		}
		if ($name === 'outbox' && $this->caldavBackend instanceof SchedulingSupport) {
			return new Outbox($this->config, $this->principalInfo['uri']);
		}
		if ($name === 'notifications' && $this->caldavBackend instanceof NotificationSupport) {
			return new Collection($this->caldavBackend, $this->principalInfo['uri']);
		}
		if ($name === TrashbinHome::NAME && $this->caldavBackend instanceof CalDavBackend) {
			return new TrashbinHome($this->caldavBackend, $this->principalInfo);
		}

		// Calendars
		foreach ($this->caldavBackend->getCalendarsForUser($this->principalInfo['uri']) as $calendar) {
			if ($calendar['uri'] === $name) {
				return new Calendar($this->caldavBackend, $calendar, $this->l10n, $this->config, $this->logger);
			}
		}

		if ($this->caldavBackend instanceof SubscriptionSupport) {
			foreach ($this->caldavBackend->getSubscriptionsForUser($this->principalInfo['uri']) as $subscription) {
				if ($subscription['uri'] === $name) {
					if ($this->returnCachedSubscriptions) {
						return new CachedSubscription($this->caldavBackend, $subscription);
					}

					return new Subscription($this->caldavBackend, $subscription);
				}
			}
		}

		if (ExternalCalendar::isAppGeneratedCalendar($name)) {
			[$appId, $calendarUri] = ExternalCalendar::splitAppGeneratedCalendarUri($name);

			foreach ($this->pluginManager->getCalendarPlugins() as $calendarPlugin) {
				if ($calendarPlugin->getAppId() !== $appId) {
					continue;
				}

				if ($calendarPlugin->hasCalendarInCalendarHome($this->principalInfo['uri'], $calendarUri)) {
					return $calendarPlugin->getCalendarInCalendarHome($this->principalInfo['uri'], $calendarUri);
				}
			}
		}

		throw new NotFound('Node with name \'' . $name . '\' could not be found');
	}

	/**
	 * @param array $filters
	 * @param integer|null $limit
	 * @param integer|null $offset
	 */
	public function calendarSearch(array $filters, int $limit = null, int $offset = null): array {
		$principalUri = $this->principalInfo['uri'];
		return $this->caldavBackend->calendarSearch($principalUri, $filters, $limit, $offset);
	}


	public function enableCachedSubscriptionsForThisRequest() {
		$this->returnCachedSubscriptions = true;
	}
}
