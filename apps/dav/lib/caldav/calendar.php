<?php

namespace OCA\DAV\CalDAV;

use OCA\DAV\DAV\Sharing\IShareable;
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
		$acl = parent::getACL();

		/** @var CalDavBackend $calDavBackend */
		$calDavBackend = $this->caldavBackend;
		return $calDavBackend->applyShareAcl($this->getResourceId(), $acl);
	}

	function getChildACL() {
		$acl = parent::getChildACL();

		/** @var CalDavBackend $calDavBackend */
		$calDavBackend = $this->caldavBackend;
		return $calDavBackend->applyShareAcl($this->getResourceId(), $acl);
	}

	function getOwner() {
		if (isset($this->calendarInfo['{http://owncloud.org/ns}owner-principal'])) {
			return $this->calendarInfo['{http://owncloud.org/ns}owner-principal'];
		}
		return parent::getOwner();
	}

	function delete() {
		if ($this->getName() === BirthdayService::BIRTHDAY_CALENDAR_URI) {
			throw new Forbidden();
		}

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
}
