<?php
/**
 * @author Lukas Reschke <lukas@owncloud.com>
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
use Sabre\CalDAV\Backend\BackendInterface;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\PropPatch;

class Calendar extends \Sabre\CalDAV\Calendar implements IShareable {

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

	private function canWrite() {
		if (isset($this->calendarInfo['{http://owncloud.org/ns}read-only'])) {
			return !$this->calendarInfo['{http://owncloud.org/ns}read-only'];
		}
		return true;
	}

}
