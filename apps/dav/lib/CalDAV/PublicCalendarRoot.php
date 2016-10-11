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
use Sabre\DAV\Exception\NotFound;

class PublicCalendarRoot extends Collection {

	/** @var CalDavBackend */
	protected $caldavBackend;

	/** @var \OCP\IL10N */
	protected $l10n;

	function __construct(CalDavBackend $caldavBackend) {
		$this->caldavBackend = $caldavBackend;
		$this->l10n = \OC::$server->getL10N('dav');
	}

	/**
	 * @inheritdoc
	 */
	function getName() {
		return 'public-calendars';
	}

	/**
	 * @inheritdoc
	 */
	function getChild($name) {
		$calendar = $this->caldavBackend->getPublicCalendar($name);
		return new Calendar($this->caldavBackend, $calendar, $this->l10n);
	}

	/**
	 * @inheritdoc
	 */
	function getChildren() {
		$calendars = $this->caldavBackend->getPublicCalendars();
		$children = [];
		foreach ($calendars as $calendar) {
			// TODO: maybe implement a new class PublicCalendar ???
			$children[] = new Calendar($this->caldavBackend, $calendar, $this->l10n);
		}

		return $children;
	}
}
