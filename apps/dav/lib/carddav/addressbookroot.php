<?php

namespace OCA\DAV\CardDAV;

class AddressBookRoot extends \Sabre\CardDAV\AddressBookRoot {

	/**
	 * This method returns a node for a principal.
	 *
	 * The passed array contains principal information, and is guaranteed to
	 * at least contain a uri item. Other properties may or may not be
	 * supplied by the authentication backend.
	 *
	 * @param array $principal
	 * @return \Sabre\DAV\INode
	 */
	function getChildForPrincipal(array $principal) {

		return new UserAddressBooks($this->carddavBackend, $principal['uri']);

	}

}