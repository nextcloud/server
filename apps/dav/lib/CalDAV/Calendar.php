<?php
/**
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
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

use OCA\DAV\DAV\Sharing\IShareable;
use OCP\IL10N;
use Sabre\CalDAV\Backend\BackendInterface;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\PropPatch;

class Calendar extends \Sabre\CalDAV\Calendar implements IShareable {

	public function __construct(BackendInterface $caldavBackend, $calendarInfo, IL10N $l10n) {
		parent::__construct($caldavBackend, $calendarInfo);

		if ($this->getName() === BirthdayService::BIRTHDAY_CALENDAR_URI) {
			$this->calendarInfo['{DAV:}displayname'] = $l10n->t('Contact birthdays');
		}
	}

	/**
	 * Updates the list of shares.
	 *
	 * The first array is a list of people that are to be added to the
	 * resource.
	 *
	 * Every element in the add array has the following properties:
	 *   * href - A url. Usually a mailto: address
	 *   * commonName - Usually a first and last name, or false
	 *   * summary - A description of the share, can also be false
	 *   * readOnly - A boolean value
	 *
	 * Every element in the remove array is just the address string.
	 *
	 * @param array $add
	 * @param array $remove
	 * @return void
	 */
	function updateShares(array $add, array $remove) {
		/** @var CalDavBackend $calDavBackend */
		$calDavBackend = $this->caldavBackend;
		$calDavBackend->updateShares($this, $add, $remove);
	}

	/**
	 * Returns the list of people whom this resource is shared with.
	 *
	 * Every element in this array should have the following properties:
	 *   * href - Often a mailto: address
	 *   * commonName - Optional, for example a first + last name
	 *   * status - See the Sabre\CalDAV\SharingPlugin::STATUS_ constants.
	 *   * readOnly - boolean
	 *   * summary - Optional, a description for the share
	 *
	 * @return array
	 */
	function getShares() {
		/** @var CalDavBackend $calDavBackend */
		$calDavBackend = $this->caldavBackend;
		return $calDavBackend->getShares($this->getResourceId());
	}

	/**
	 * @return int
	 */
	public function getResourceId() {
		return $this->calendarInfo['id'];
	}

	function getACL() {
		$acl =  [
			[
				'privilege' => '{DAV:}read',
				'principal' => $this->getOwner(),
				'protected' => true,
			]];
		if ($this->getName() !== BirthdayService::BIRTHDAY_CALENDAR_URI) {
			$acl[] = [
				'privilege' => '{DAV:}write',
				'principal' => $this->getOwner(),
				'protected' => true,
			];
		}
		if ($this->getOwner() !== parent::getOwner()) {
			$acl[] =  [
					'privilege' => '{DAV:}read',
					'principal' => parent::getOwner(),
					'protected' => true,
				];
			if ($this->canWrite()) {
				$acl[] = [
					'privilege' => '{DAV:}write',
					'principal' => parent::getOwner(),
					'protected' => true,
				];
			}
		}

		/** @var CalDavBackend $calDavBackend */
		$calDavBackend = $this->caldavBackend;
		return $calDavBackend->applyShareAcl($this->getResourceId(), $acl);
	}

	function getChildACL() {
		return $this->getACL();
	}

	function getOwner() {
		if (isset($this->calendarInfo['{http://owncloud.org/ns}owner-principal'])) {
			return $this->calendarInfo['{http://owncloud.org/ns}owner-principal'];
		}
		return parent::getOwner();
	}

	function delete() {
		if (isset($this->calendarInfo['{http://owncloud.org/ns}owner-principal'])) {
			$principal = 'principal:' . parent::getOwner();
			$shares = $this->getShares();
			$shares = array_filter($shares, function($share) use ($principal){
				return $share['href'] === $principal;
			});
			if (empty($shares)) {
				throw new Forbidden();
			}

			/** @var CalDavBackend $calDavBackend */
			$calDavBackend = $this->caldavBackend;
			$calDavBackend->updateShares($this, [], [
				'href' => $principal
			]);
			return;
		}
		parent::delete();
	}

	function propPatch(PropPatch $propPatch) {
		$mutations = $propPatch->getMutations();
		// If this is a shared calendar, the user can only change the enabled property, to hide it.
		if (isset($this->calendarInfo['{http://owncloud.org/ns}owner-principal']) && (sizeof($mutations) !== 1 || !isset($mutations['{http://owncloud.org/ns}calendar-enabled']))) {
			throw new Forbidden();
		}
		parent::propPatch($propPatch);
	}

	function getChild($name) {

		$obj = $this->caldavBackend->getCalendarObject($this->calendarInfo['id'], $name);

		if (!$obj) {
			throw new NotFound('Calendar object not found');
		}

		if ($this->isShared() && $obj['classification'] === CalDavBackend::CLASSIFICATION_PRIVATE) {
			throw new NotFound('Calendar object not found');
		}

		$obj['acl'] = $this->getChildACL();

		return new CalendarObject($this->caldavBackend, $this->calendarInfo, $obj);

	}

	function getChildren() {

		$objs = $this->caldavBackend->getCalendarObjects($this->calendarInfo['id']);
		$children = [];
		foreach ($objs as $obj) {
			if ($this->isShared() && $obj['classification'] === CalDavBackend::CLASSIFICATION_PRIVATE) {
				continue;
			}
			$obj['acl'] = $this->getChildACL();
			$children[] = new CalendarObject($this->caldavBackend, $this->calendarInfo, $obj);
		}
		return $children;

	}

	function getMultipleChildren(array $paths) {

		$objs = $this->caldavBackend->getMultipleCalendarObjects($this->calendarInfo['id'], $paths);
		$children = [];
		foreach ($objs as $obj) {
			if ($this->isShared() && $obj['classification'] === CalDavBackend::CLASSIFICATION_PRIVATE) {
				continue;
			}
			$obj['acl'] = $this->getChildACL();
			$children[] = new CalendarObject($this->caldavBackend, $this->calendarInfo, $obj);
		}
		return $children;

	}

	function childExists($name) {
		$obj = $this->caldavBackend->getCalendarObject($this->calendarInfo['id'], $name);
		if (!$obj) {
			return false;
		}
		if ($this->isShared() && $obj['classification'] === CalDavBackend::CLASSIFICATION_PRIVATE) {
			return false;
		}

		return true;
	}

	function calendarQuery(array $filters) {

		$uris = $this->caldavBackend->calendarQuery($this->calendarInfo['id'], $filters);
		if ($this->isShared()) {
			return array_filter($uris, function ($uri) {
				return $this->childExists($uri);
			});
		}

		return $uris;
	}

	private function canWrite() {
		if (isset($this->calendarInfo['{http://owncloud.org/ns}read-only'])) {
			return !$this->calendarInfo['{http://owncloud.org/ns}read-only'];
		}
		return true;
	}

	private function isShared() {
		return isset($this->calendarInfo['{http://owncloud.org/ns}owner-principal']);
	}

}
