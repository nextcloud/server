<?php

namespace OCA\DAV\CardDAV;

class UserAddressBooks extends \Sabre\CardDAV\AddressBookHome {

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
		if ($this->principalUri === 'principals/system') {
			$acl[] = [
					'privilege' => '{DAV:}read',
					'principal' => '{DAV:}authenticated',
					'protected' => true,
			];
		}

		return $acl;
	}

}
