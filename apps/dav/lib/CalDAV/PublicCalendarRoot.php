<?php
/**
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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

use Sabre\DAV\Collection;

class PublicCalendarRoot extends Collection {

	/** @var CalDavBackend */
	protected $caldavBackend;

	function __construct(CalDavBackend $caldavBackend) {
		$this->caldavBackend = $caldavBackend;
	}

	/**
	 * @inheritdoc
	 */
	function getName() {
		return 'public-calendars';
	}

	function getChild($name) {
		// TODO: for performance reason this needs to have a custom implementation
		return parent::getChild($name);
	}

	/**
	 * @inheritdoc
	 */
	function getChildren() {
		$l10n = \OC::$server->getL10N('dav');
		$calendars = $this->caldavBackend->getPublicCalendars();
		$children = [];
		foreach ($calendars as $calendar) {
			// TODO: maybe implement a new class PublicCalendar ???
			$children[] = new Calendar($this->caldavBackend, $calendar, $l10n);
		}

		return $children;
	}
}
