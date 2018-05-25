<?php
/**
 * @copyright Copyright (c) 2016, Roeland Jago Douma <roeland@famdouma.nl>
 * @copyright Copyright (c) 2016, Joas Schilling <coding@schilljs.com>
 *
 * @author Joas Schilling <coding@schilljs.com>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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
 *
 */
namespace OCA\DAV\CalDAV\Schedule;

use OCA\DAV\CalDAV\CalDavBackend;
use OCA\DAV\CalDAV\CalendarHome;
use Sabre\DAV\INode;
use Sabre\DAV\PropFind;
use Sabre\DAV\Server;
use Sabre\DAV\Xml\Property\LocalHref;
use Sabre\DAVACL\IPrincipal;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Reader;

class Plugin extends \Sabre\CalDAV\Schedule\Plugin {

	/**
	 * Initializes the plugin
	 *
	 * @param Server $server
	 * @return void
	 */
	function initialize(Server $server) {
		parent::initialize($server);
		$server->on('propFind', [$this, 'propFindDefaultCalendarUrl'], 90);
	}

	/**
	 * Returns a list of addresses that are associated with a principal.
	 *
	 * @param string $principal
	 * @return array
	 */
	protected function getAddressesForPrincipal($principal) {
		$result = parent::getAddressesForPrincipal($principal);

		if ($result === null) {
			$result = [];
		}

		return $result;
	}

	/**
	 * Always use the personal calendar as target for scheduled events
	 *
	 * @param PropFind $propFind
	 * @param INode $node
	 * @return void
	 */
	function propFindDefaultCalendarUrl(PropFind $propFind, INode $node) {
		if ($node instanceof IPrincipal) {
			$propFind->handle('{' . self::NS_CALDAV . '}schedule-default-calendar-URL', function() use ($node) {
				/** @var \OCA\DAV\CalDAV\Plugin $caldavPlugin */
				$caldavPlugin = $this->server->getPlugin('caldav');
				$principalUrl = $node->getPrincipalUrl();

				$calendarHomePath = $caldavPlugin->getCalendarHomeForPrincipal($principalUrl);

				if (!$calendarHomePath) {
					return null;
				}

				/** @var CalendarHome $calendarHome */
				$calendarHome = $this->server->tree->getNodeForPath($calendarHomePath);
				if (!$calendarHome->childExists(CalDavBackend::PERSONAL_CALENDAR_URI)) {
					$calendarHome->getCalDAVBackend()->createCalendar($principalUrl, CalDavBackend::PERSONAL_CALENDAR_URI, [
						'{DAV:}displayname' => CalDavBackend::PERSONAL_CALENDAR_NAME,
					]);
				}

				$result = $this->server->getPropertiesForPath($calendarHomePath . '/' . CalDavBackend::PERSONAL_CALENDAR_URI, [], 1);
				if (empty($result)) {
					return null;
				}

				return new LocalHref($result[0]['href']);
			});
		}
	}

	/**
	 * This method is triggered whenever there was a calendar object gets
	 * created or updated.
	 *
	 * Basically just a copy of parent::calendarObjectChange, with the change
	 * from:
	 * $addresses = $this->getAddressesForPrincipal($calendarNode->getOwner());
	 * to:
	 * $addresses = $this->getAddressesForPrincipal($calendarNode->getPrincipalURI());
	 *
	 * @param RequestInterface $request HTTP request
	 * @param ResponseInterface $response HTTP Response
	 * @param VCalendar $vCal Parsed iCalendar object
	 * @param mixed $calendarPath Path to calendar collection
	 * @param mixed $modified The iCalendar object has been touched.
	 * @param mixed $isNew Whether this was a new item or we're updating one
	 * @return void
	 */
	function calendarObjectChange(RequestInterface $request, ResponseInterface $response, VCalendar $vCal, $calendarPath, &$modified, $isNew) {

		if (!$this->scheduleReply($this->server->httpRequest)) {
			return;
		}

		$calendarNode = $this->server->tree->getNodeForPath($calendarPath);

		$addresses = $this->getAddressesForPrincipal(
			$calendarNode->getPrincipalURI()
		);

		if (!$isNew) {
			$node = $this->server->tree->getNodeForPath($request->getPath());
			$oldObj = Reader::read($node->get());
		} else {
			$oldObj = null;
		}

		$this->processICalendarChange($oldObj, $vCal, $addresses, [], $modified);

		if ($oldObj) {
			// Destroy circular references so PHP will GC the object.
			$oldObj->destroy();
		}

	}

	/**
	 * This method checks the 'Schedule-Reply' header
	 * and returns false if it's 'F', otherwise true.
	 *
	 * Copied from Sabre/DAV's Schedule plugin, because it's
	 * private for whatever reason
	 *
	 * @param RequestInterface $request
	 * @return bool
	 */
	private function scheduleReply(RequestInterface $request) {

		$scheduleReply = $request->getHeader('Schedule-Reply');
		return $scheduleReply !== 'F';

	}
}
