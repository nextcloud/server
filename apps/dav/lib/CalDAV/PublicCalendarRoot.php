<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\CalDAV;

use Sabre\DAV\Collection;

class PublicCalendarRoot extends Collection {

	/**
	 * PublicCalendarRoot constructor.
	 *
	 * @param CalDavBackend $caldavBackend
	 */
	public function __construct(
		protected CalDavBackend $caldavBackend,
		private readonly CalendarFactory $calendarFactory,
	) {
	}

	/**
	 * @inheritdoc
	 */
	public function getName() {
		return 'public-calendars';
	}

	/**
	 * @inheritdoc
	 */
	public function getChild($name) {
		$calendar = $this->caldavBackend->getPublicCalendar($name);
		return $this->calendarFactory->createPublicCalendar($calendar);
	}

	/**
	 * @inheritdoc
	 */
	public function getChildren() {
		return [];
	}
}
