<?php
/**
 * ownCloud - Addressbook
 *
 * @author Thomas Tanghus
 * @copyright 2012 Thomas Tanghus (thomas@tanghus.net)
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

/**
 * This class overrides __construct to get access to $addressBookInfo and
 * $carddavBackend, Sabre_CardDAV_AddressBook::getACL() to return read/write
 * permissions based on user and shared state and it overrides
 * Sabre_CardDAV_AddressBook::getChild() and Sabre_CardDAV_AddressBook::getChildren()
 * to instantiate OC_Connector_Sabre_CardDAV_Cards.
*/
class OC_Connector_Sabre_CardDAV_AddressBook extends Sabre_CardDAV_AddressBook {

	/**
	* CardDAV backend
	*
	* @var Sabre_CardDAV_Backend_Abstract
	*/
	private $carddavBackend;

	/**
	* Constructor
	*
	* @param Sabre_CardDAV_Backend_Abstract $carddavBackend
	* @param array $addressBookInfo
	*/
	public function __construct(
		Sabre_CardDAV_Backend_Abstract $carddavBackend,
		array $addressBookInfo) {

		$this->carddavBackend = $carddavBackend;
		$this->addressBookInfo = $addressBookInfo;
		parent::__construct($carddavBackend, $addressBookInfo);

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
	public function getACL() {

		$readprincipal = $this->getOwner();
		$writeprincipal = $this->getOwner();
		$uid = OC_Contacts_Addressbook::extractUserID($this->getOwner());

		if($uid != OCP\USER::getUser()) {
			$sharedAddressbook = OCP\Share::getItemSharedWithBySource('addressbook', $this->addressBookInfo['id']);
			if ($sharedAddressbook && ($sharedAddressbook['permissions'] & OCP\Share::PERMISSION_READ)) {
				$readprincipal = 'principals/' . OCP\USER::getUser();
			}
			if ($sharedAddressbook && ($sharedAddressbook['permissions'] & OCP\Share::PERMISSION_UPDATE)) {
				$writeprincipal = 'principals/' . OCP\USER::getUser();
			}
		}

		return array(
			array(
				'privilege' => '{DAV:}read',
				'principal' => $readprincipal,
				'protected' => true,
			),
			array(
				'privilege' => '{DAV:}write',
				'principal' => $writeprincipal,
				'protected' => true,
			),

		);

	}

	/**
	* Returns a card
	*
	* @param string $name
	* @return OC_Connector_Sabre_DAV_Card
	*/
	public function getChild($name) {

		$obj = $this->carddavBackend->getCard($this->addressBookInfo['id'],$name);
		if (!$obj) throw new Sabre_DAV_Exception_NotFound('Card not found');
		return new OC_Connector_Sabre_CardDAV_Card($this->carddavBackend,$this->addressBookInfo,$obj);

	}

	/**
	* Returns the full list of cards
	*
	* @return array
	*/
	public function getChildren() {

		$objs = $this->carddavBackend->getCards($this->addressBookInfo['id']);
		$children = array();
		foreach($objs as $obj) {
			$children[] = new OC_Connector_Sabre_CardDAV_Card($this->carddavBackend,$this->addressBookInfo,$obj);
		}
		return $children;

	}
}