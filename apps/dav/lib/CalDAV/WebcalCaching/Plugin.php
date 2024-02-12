<?php

declare(strict_types=1);

/**
 * @copyright 2018 Georg Ehrke <oc.list@georgehrke.com>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Georg Ehrke <oc.list@georgehrke.com>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCA\DAV\CalDAV\WebcalCaching;

use OCA\DAV\CalDAV\CachedSubscription;
use OCA\DAV\CalDAV\CalDavBackend;
use OCA\DAV\CalDAV\CalendarHome;
use OCP\IRequest;
use Sabre\CalDAV\Backend\SubscriptionSupport;
use Sabre\CalDAV\CalendarQueryValidator;
use Sabre\CalDAV\ICalendarObject;
use Sabre\CalDAV\ICalendarObjectContainer;
use Sabre\CalDAV\Subscriptions\ISubscription;
use Sabre\DAV\Exception\BadRequest;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\INode;
use Sabre\DAV\PropFind;
use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

class Plugin extends ServerPlugin {

	/**
	 * list of regular expressions for calendar user agents,
	 * that do not support subscriptions on their own
	 *
	 * /^MSFT-WIN-3/ - Windows 10 Calendar
	 * @var string[]
	 */
	public const ENABLE_FOR_CLIENTS = [
		"/^MSFT-WIN-3/"
	];

	/**
	 * @var bool
	 */
	private $enabled = false;

	/**
	 * @var Server
	 */
	private $server;

	/**
	 * Plugin constructor.
	 *
	 * @param IRequest $request
	 */
	public function __construct(IRequest $request) {
		if ($request->isUserAgent(self::ENABLE_FOR_CLIENTS)) {
			$this->enabled = true;
		}

		$magicHeader = $request->getHeader('X-NC-CalDAV-Webcal-Caching');
		if ($magicHeader === 'On') {
			$this->enabled = true;
		}
	}

	/**
	 * This initializes the plugin.
	 *
	 * This function is called by Sabre\DAV\Server, after
	 * addPlugin is called.
	 *
	 * This method should set up the required event subscriptions.
	 *
	 * @param Server $server
	 */
	public function initialize(Server $server) {
		$this->server = $server;
		$server->on('beforeMethod:*', [$this, 'beforeMethod']);
		$server->on('propFind', [$this, 'propFind']);
		$server->on('report', [$this, 'report']);
	}

	/**
	 * @param RequestInterface $request
	 * @param ResponseInterface $response
	 */
	public function beforeMethod(RequestInterface $request, ResponseInterface $response) {
		if (!$this->enabled) {
			return;
		}

		$path = $request->getPath();
		$pathParts = explode('/', ltrim($path, '/'));
		if (\count($pathParts) < 2) {
			return;
		}

		// $calendarHomePath will look like: calendars/username
		$calendarHomePath = $pathParts[0] . '/' . $pathParts[1];
		try {
			// GetNodeForPath returns a OCA\DAV\Principal\Collection
			$calendarHome = $this->server->tree->getNodeForPath($calendarHomePath);
			if (!($calendarHome instanceof CalendarHome)) {
				//how did we end up here?
				return;
			}

			$calendarHome->enableCachedSubscriptionsForThisRequest();
		} catch (NotFound $ex) {
			return;
		}
	}

	public function report($reportName, $report, $path) {
		if(!$this->enabled) {
			return;
		}

		$pathParts = explode('/', ltrim($path, '/'));

		// $calendarHomePath will look like: calendars/username
		$calendarHomePath = $pathParts[0] . '/' . $pathParts[1];
		try {
			// GetNodeForPath returns a OCA\DAV\Principal\Collection
			$calendarHome = $this->server->tree->getNodeForPath($calendarHomePath);
			if (!($calendarHome instanceof CalendarHome)) {
				//how did we end up here?
				return;
			}
			$calendarHome->enableCachedSubscriptionsForThisRequest();
			// Cache Waming maybe?
			$child = $calendarHome->getChild($pathParts[2]);
		} catch (NotFound $ex) {
			return;
		}

		// We only handle Cached Subscription Calendars
		if(!$child instanceof CachedSubscription) {
			return;
		}

		$path = $this->server->getRequestUri();

		$needsJson = 'application/calendar+json' === $report->contentType;

		$depth = $this->server->getHTTPDepth(0);

		// The default result is an empty array
		$result = [];

		$calendarTimeZone = null;
		if ($report->expand) {
			// We're expanding, and for that we need to figure out the
			// calendar's timezone.
			$tzProp = '{'.\Sabre\CalDAV\Plugin::NS_CALDAV.'}calendar-timezone';
			$tzResult = $this->server->getProperties($path, [$tzProp]);
			if (isset($tzResult[$tzProp])) {
				// This property contains a VCALENDAR with a single
				// VTIMEZONE.
				$vtimezoneObj = VObject\Reader::read($tzResult[$tzProp]);
				$calendarTimeZone = $vtimezoneObj->VTIMEZONE->getTimeZone();

				// Destroy circular references so PHP will garbage collect the
				// object.
				$vtimezoneObj->destroy();
			} else {
				// Defaulting to UTC.
				$calendarTimeZone = new DateTimeZone('UTC');
			}
		}

		// If we're dealing with a calendar, the calendar itself is responsible
		// for the calendar-query.
		if ($child instanceof ICalendarObjectContainer && 1 == $depth) {
			$nodePaths = $child->calendarQuery($report->filters);

			foreach ($nodePaths as $path) {
				list($properties) =
					$this->server->getPropertiesForPath($this->server->getRequestUri().'/'.$path, $report->properties);

				if (($needsJson || $report->expand)) {
					$vObject = VObject\Reader::read($properties[200]['{'.\Sabre\CalDAV\Plugin::NS_CALDAV.'}calendar-data']);

					if ($report->expand) {
						$vObject = $vObject->expand($report->expand['start'], $report->expand['end'], $calendarTimeZone);
					}

					if ($needsJson) {
						$properties[200]['{'.\Sabre\CalDAV\Plugin::NS_CALDAV.'}calendar-data'] = json_encode($vObject->jsonSerialize());
					} else {
						$properties[200]['{'.\Sabre\CalDAV\Plugin::NS_CALDAV.'}calendar-data'] = $vObject->serialize();
					}

					// Destroy circular references so PHP will garbage collect the
					// object.
					$vObject->destroy();
				}
				$result[] = $properties;
			}
		}

		$prefer = $this->server->getHTTPPrefer();

		$this->server->httpResponse->setStatus(207);
		$this->server->httpResponse->setHeader('Content-Type', 'application/xml; charset=utf-8');
		$this->server->httpResponse->setHeader('Vary', 'Brief,Prefer');
		$this->server->httpResponse->setBody($this->server->generateMultiStatus($result, 'minimal' === $prefer['return']));

	}

	public function propFind(PropFind $propFind, INode $node) {
		if(!$this->enabled) {
			return;
		}

		if($node instanceof CalendarHome) {
			$node->enableCachedSubscriptionsForThisRequest();
		}
	}

	/**
	 * @return bool
	 */
	public function isCachingEnabledForThisRequest():bool {
		return $this->enabled;
	}

	/**
	 * This method should return a list of server-features.
	 *
	 * This is for example 'versioning' and is added to the DAV: header
	 * in an OPTIONS response.
	 *
	 * @return string[]
	 */
	public function getFeatures():array {
		return ['nc-calendar-webcal-cache'];
	}

	/**
	 * Returns a plugin name.
	 *
	 * Using this name other plugins will be able to access other plugins
	 * using Sabre\DAV\Server::getPlugin
	 *
	 * @return string
	 */
	public function getPluginName():string {
		return 'nc-calendar-webcal-cache';
	}
}
