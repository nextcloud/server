<?php
/**
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @copyright Copyright (c) 2016, ownCloud GmbH.
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\DAV\CalDAV;

use Sabre\DAV\Server;
use Sabre\DAV;
use Sabre\DAV\Xml\Property\LocalHref;
use Sabre\DAVACL\IPrincipal;

class Plugin extends \Sabre\CalDAV\Plugin implements ICalendarHomePlugin {

	/**
	 * Initializes the plugin
	 *
	 * @param Server $server
	 * @return void
	 */
	public function initialize(Server $server) {
		parent::initialize($server);
		$server->on('propFind', [$this, 'propFind']);
	}

	/**
	 * PropFind
	 *
	 * This method handler is invoked before any after properties for a
	 * resource are fetched. This allows us to add in any CalDAV specific
	 * properties.
	 *
	 * @param DAV\PropFind $propFind
	 * @param DAV\INode $node
	 * @return void
	 */
	public function propFind(DAV\PropFind $propFind, DAV\INode $node) {
		parent::propFind($propFind, $node);
		if ($node instanceof IPrincipal) {
			$principalUrl = $node->getPrincipalUrl();
			$propFind->handle('{' . self::NS_CALDAV . '}calendar-home-set', function () use ($principalUrl) {
				$calendarHomes = [];
				// Make sure the dav apps caldav endpoint is at the first place
				$calendarHomePath = $this->getCalendarHomeForPrincipal($principalUrl);
				if ($calendarHomePath !== null) {
					$calendarHomes[] = $calendarHomePath;
				}
				foreach ($this->server->getPlugins() as $plugin) {
					if ($plugin instanceof ICalendarHomePlugin && $plugin !== $this) {
						$calendarHomePath = $plugin->getCalendarHomeForPrincipal($principalUrl);
						if ($calendarHomePath !== null) {
							$calendarHomes[] = $calendarHomePath;
						}
					}
				}
				return new LocalHref($calendarHomes);
			});
		}
	}

	/**
	 * Add the Nextcloud default calendar home
	 *
	 * @inheritdoc
	 */
	public function getCalendarHomeForPrincipal($principalUrl) {
		if (strrpos($principalUrl, 'principals/users', -strlen($principalUrl)) !== false) {
			list(, $principalId) = \Sabre\Uri\split($principalUrl);
			return self::CALENDAR_ROOT .'/' . $principalId;
		}
		return null;
	}

}
