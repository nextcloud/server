<?php

namespace OCA\DAV\CardDAV;

class UserAddressBooks extends \Sabre\CardDAV\UserAddressBooks {

	/**
	 * Returns a list of addressbooks
	 *
	 * @return array
	 */
	function getChildren() {

		$addressbooks = $this->carddavBackend->getAddressBooksForUser($this->principalUri);
		$objs = [];
		foreach($addressbooks as $addressbook) {
			$objs[] = new AddressBook($this->carddavBackend, $addressbook);
		}
		return $objs;

	}

}
