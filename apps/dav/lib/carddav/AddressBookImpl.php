<?php
/**
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
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

class AddressBookImpl implements \OCP\IAddressBook {

	public function __construct(array $values, CardDavBackend $backend) {
		$this->values = $values;
		$this->backend = $backend;
		/*
		 * 				'id'  => $row['id'],
				'uri' => $row['uri'],
				'principaluri' => $row['principaluri'],
				'{DAV:}displayname' => $row['displayname'],
				'{' . Plugin::NS_CARDDAV . '}addressbook-description' => $row['description'],
				'{http://calendarserver.org/ns/}getctag' => $row['synctoken'],
				'{http://sabredav.org/ns}sync-token' => $row['synctoken']?$row['synctoken']:'0',
*/
	}

	/**
	 * @return string defining the technical unique key
	 * @since 5.0.0
	 */
	public function getKey() {
		return $this->values['id'];
	}

	/**
	 * In comparison to getKey() this function returns a human readable (maybe translated) name
	 *
	 * @return mixed
	 * @since 5.0.0
	 */
	public function getDisplayName() {
		return $this->values['{DAV:}displayname'];
	}

	/**
	 * @param string $pattern which should match within the $searchProperties
	 * @param array $searchProperties defines the properties within the query pattern should match
	 * @param array $options - for future use. One should always have options!
	 * @return array an array of contacts which are arrays of key-value-pairs
	 * @since 5.0.0
	 */
	public function search($pattern, $searchProperties, $options) {
		// TODO: Implement search() method.
		$cards = $this->backend->getCards($this->values['id']);

		//
		// TODO: search now
		//

	}

	/**
	 * @param array $properties this array if key-value-pairs defines a contact
	 * @return array an array representing the contact just created or updated
	 * @since 5.0.0
	 */
	public function createOrUpdate($properties) {
		// TODO: Implement createOrUpdate() method.
	}

	/**
	 * @return mixed
	 * @since 5.0.0
	 */
	public function getPermissions() {
		// TODO: Implement getPermissions() method.
	}

	/**
	 * @param object $id the unique identifier to a contact
	 * @return bool successful or not
	 * @since 5.0.0
	 */
	public function delete($id) {
		// TODO: Implement delete() method.
	}
}
