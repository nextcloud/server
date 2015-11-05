<?php

namespace OCA\DAV\CardDAV;

use OCA\DAV\CardDAV\Sharing\IShareableAddressBook;
use OCP\IUserManager;

class AddressBook extends \Sabre\CardDAV\AddressBook implements IShareableAddressBook {

	/** @var IUserManager */
	private $userManager;

	public function __construct(CardDavBackend $carddavBackend, array $addressBookInfo) {
		parent::__construct($carddavBackend, $addressBookInfo);
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
		$carddavBackend->updateShares($this->getName(), $add, $remove);
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
		$carddavBackend->getShares($this->getName());
	}
}