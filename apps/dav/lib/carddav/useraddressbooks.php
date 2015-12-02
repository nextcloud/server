<?php

namespace OCA\DAV\CardDAV;

class UserAddressBooks extends \Sabre\CardDAV\AddressBookHome {

	/**
	 * Returns a list of addressbooks
	 *
	 * @return array
	 */
	function getChildren() {

		$addressBooks = $this->carddavBackend->getAddressBooksForUser($this->principalUri);
		$objects = [];
		foreach($addressBooks as $addressBook) {
			$objects[] = new AddressBook($this->carddavBackend, $addressBook);
		}
		return $objects;

	}

	/**
	 * Returns a list of ACE's for this node.
	 *
	 * Each ACE has the following properties:
	 *   * 'privilege', a string such as {DAV:}read or {DAV:}write. These are
	 *     currently the only supported privileges
	 *   * 'principal', a url to the principal who owns the node
	 *   * 'protected' (optional), indicating that this ACE is not allowed to
	 *      be updated.
	 *
	 * @return array
	 */
	function getACL() {

		$acl = parent::getACL();
		if ($this->principalUri === 'principals/system/system') {
			$acl[] = [
					'privilege' => '{DAV:}read',
					'principal' => '{DAV:}authenticated',
					'protected' => true,
			];
		}

		return $acl;
	}

}
