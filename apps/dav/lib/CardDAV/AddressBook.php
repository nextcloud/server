<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
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
namespace OCA\DAV\CardDAV;

use OCA\DAV\DAV\Sharing\IShareable;
use OCP\IL10N;
use Sabre\CardDAV\Backend\BackendInterface;
use Sabre\CardDAV\Card;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\PropPatch;

class AddressBook extends \Sabre\CardDAV\AddressBook implements IShareable {

	/**
	 * AddressBook constructor.
	 *
	 * @param BackendInterface $carddavBackend
	 * @param array $addressBookInfo
	 * @param IL10N $l10n
	 */
	public function __construct(BackendInterface $carddavBackend, array $addressBookInfo, IL10N $l10n) {
		parent::__construct($carddavBackend, $addressBookInfo);

		if ($this->getName() === CardDavBackend::PERSONAL_ADDRESSBOOK_URI &&
			$this->addressBookInfo['{DAV:}displayname'] === CardDavBackend::PERSONAL_ADDRESSBOOK_NAME) {
			$this->addressBookInfo['{DAV:}displayname'] = $l10n->t('Contacts');
		}
	}

	/**
	 * Updates the list of shares.
	 *
	 * The first array is a list of people that are to be added to the
	 * addressbook.
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
		/** @var CardDavBackend $carddavBackend */
		$carddavBackend = $this->carddavBackend;
		$carddavBackend->updateShares($this, $add, $remove);
	}

	/**
	 * Returns the list of people whom this addressbook is shared with.
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
		/** @var CardDavBackend $carddavBackend */
		$carddavBackend = $this->carddavBackend;
		return $carddavBackend->getShares($this->getResourceId());
	}

	function getACL() {
		$acl =  [
			[
				'privilege' => '{DAV:}read',
				'principal' => $this->getOwner(),
				'protected' => true,
			]];
		$acl[] = [
				'privilege' => '{DAV:}write',
				'principal' => $this->getOwner(),
				'protected' => true,
			];
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
		if ($this->getOwner() === 'principals/system/system') {
			$acl[] = [
					'privilege' => '{DAV:}read',
					'principal' => '{DAV:}authenticated',
					'protected' => true,
			];
		}

		/** @var CardDavBackend $carddavBackend */
		$carddavBackend = $this->carddavBackend;
		return $carddavBackend->applyShareAcl($this->getResourceId(), $acl);
	}

	function getChildACL() {
		return $this->getACL();
	}

	function getChild($name) {

		$obj = $this->carddavBackend->getCard($this->addressBookInfo['id'], $name);
		if (!$obj) {
			throw new NotFound('Card not found');
		}
		$obj['acl'] = $this->getChildACL();
		return new Card($this->carddavBackend, $this->addressBookInfo, $obj);

	}

	/**
	 * @return int
	 */
	public function getResourceId() {
		return $this->addressBookInfo['id'];
	}

	function getOwner() {
		if (isset($this->addressBookInfo['{http://owncloud.org/ns}owner-principal'])) {
			return $this->addressBookInfo['{http://owncloud.org/ns}owner-principal'];
		}
		return parent::getOwner();
	}

	function delete() {
		if (isset($this->addressBookInfo['{http://owncloud.org/ns}owner-principal'])) {
			$principal = 'principal:' . parent::getOwner();
			$shares = $this->getShares();
			$shares = array_filter($shares, function($share) use ($principal){
				return $share['href'] === $principal;
			});
			if (empty($shares)) {
				throw new Forbidden();
			}

			/** @var CardDavBackend $cardDavBackend */
			$cardDavBackend = $this->carddavBackend;
			$cardDavBackend->updateShares($this, [], [
				'href' => $principal
			]);
			return;
		}
		parent::delete();
	}

	function propPatch(PropPatch $propPatch) {
		if (isset($this->addressBookInfo['{http://owncloud.org/ns}owner-principal'])) {
			throw new Forbidden();
		}
		parent::propPatch($propPatch);
	}

	public function getContactsGroups() {
		/** @var CardDavBackend $cardDavBackend */
		$cardDavBackend = $this->carddavBackend;

		return $cardDavBackend->collectCardProperties($this->getResourceId(), 'CATEGORIES');
	}

	private function canWrite() {
		if (isset($this->addressBookInfo['{http://owncloud.org/ns}read-only'])) {
			return !$this->addressBookInfo['{http://owncloud.org/ns}read-only'];
		}
		return true;
	}
}
