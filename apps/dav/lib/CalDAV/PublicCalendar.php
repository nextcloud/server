<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\CalDAV;

use Sabre\DAV\Exception\NotFound;

class PublicCalendar extends Calendar {

	/**
	 * @param string $name
	 * @throws NotFound
	 * @return PublicCalendarObject
	 */
	public function getChild($name) {
		$obj = $this->caldavBackend->getCalendarObject($this->calendarInfo['id'], $name);

		if (!$obj) {
			throw new NotFound('Calendar object not found');
		}
		if ($obj['classification'] === CalDavBackend::CLASSIFICATION_PRIVATE) {
			throw new NotFound('Calendar object not found');
		}
		$obj['acl'] = $this->getChildACL();

		return new PublicCalendarObject($this->caldavBackend, $this->l10n, $this->calendarInfo, $obj);
	}

	/**
	 * @return PublicCalendarObject[]
	 */
	public function getChildren() {
		$objs = $this->caldavBackend->getCalendarObjects($this->calendarInfo['id']);
		$children = [];
		foreach ($objs as $obj) {
			if ($obj['classification'] === CalDavBackend::CLASSIFICATION_PRIVATE) {
				continue;
			}
			$obj['acl'] = $this->getChildACL();
			$children[] = new PublicCalendarObject($this->caldavBackend, $this->l10n, $this->calendarInfo, $obj);
		}
		return $children;
	}

	/**
	 * @param string[] $paths
	 * @return PublicCalendarObject[]
	 */
	public function getMultipleChildren(array $paths) {
		$objs = $this->caldavBackend->getMultipleCalendarObjects($this->calendarInfo['id'], $paths);
		$children = [];
		foreach ($objs as $obj) {
			if ($obj['classification'] === CalDavBackend::CLASSIFICATION_PRIVATE) {
				continue;
			}
			$obj['acl'] = $this->getChildACL();
			$children[] = new PublicCalendarObject($this->caldavBackend, $this->l10n, $this->calendarInfo, $obj);
		}
		return $children;
	}

	/**
	 * public calendars are always shared
	 * @return bool
	 */
	public function isShared() {
		return true;
	}
}
