<?php
/**
 * ownCloud
 *
 * @author Thomas Müller
 * @copyright 2012 Thomas Müller thomas.mueller@tmit.eu
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
 * Public interface of ownCloud for apps to use.
 * Contacts Class
 *
 */

namespace OC {
	interface IAddressBook {

		/**
		 * @return string defining the technical unique key
		 */
		public function getKey();

		/**
		 * In comparison to getKey() this function returns a human readable (maybe translated) name
		 * @return mixed
		 */
		public function getDisplayName();

		/**
		 * @param $pattern
		 * @param $searchProperties
		 * @param $options
		 * @return mixed
		 */
		public function search($pattern, $searchProperties, $options);
//			// dummy results
//			return array(
//				array('id' => 0, 'FN' => 'Thomas Müller', 'EMAIL' => 'a@b.c', 'GEO' => '37.386013;-122.082932'),
//				array('id' => 5, 'FN' => 'Thomas Tanghus', 'EMAIL' => array('d@e.f', 'g@h.i')),
//			);

		/**
		 * @param $properties
		 * @return mixed
		 */
		public function createOrUpdate($properties);
//			// dummy
//			return array('id'    => 0, 'FN' => 'Thomas Müller', 'EMAIL' => 'a@b.c',
//			             'PHOTO' => 'VALUE=uri:http://www.abc.com/pub/photos/jqpublic.gif',
//			             'ADR'   => ';;123 Main Street;Any Town;CA;91921-1234'
//			);

		/**
		 * @return mixed
		 */
		public function getPermissions();

		/**
		 * @param $id
		 * @return mixed
		 */
		public function delete($id);
	}
}

// use OCP namespace for all classes that are considered public.
// This means that they should be used by apps instead of the internal ownCloud classes
namespace OCP {

	/**
	 * This class provides access to the contacts app. Use this class exclusively if you want to access contacts.
	 *
	 * Contacts in general will be expressed as an array of key-value-pairs.
	 * The keys will match the property names defined in https://tools.ietf.org/html/rfc2426#section-1
	 *
	 * Proposed workflow for working with contacts:
	 *  - search for the contacts
	 *  - manipulate the results array
	 *  - createOrUpdate will save the given contacts overwriting the existing data
	 *
	 * For updating it is mandatory to keep the id.
	 * Without an id a new contact will be created.
	 *
	 */
	class Contacts {

		/**
		 * This function is used to search and find contacts within the users address books.
		 * In case $pattern is empty all contacts will be returned.
		 *
		 * @param string $pattern which should match within the $searchProperties
		 * @param array $searchProperties defines the properties within the query pattern should match
		 * @param array $options - for future use. One should always have options!
		 * @return array of contacts which are arrays of key-value-pairs
		 */
		public static function search($pattern, $searchProperties = array(), $options = array()) {

			$result = array();
			foreach(self::$address_books as $address_book) {
				$result = $result + $address_book->search($pattern, $searchProperties, $options);
			}

			return $result;
		}

		/**
		 * This function can be used to delete the contact identified by the given id
		 *
		 * @param object $id the unique identifier to a contact
		 * @param $address_book_key
		 * @return bool successful or not
		 */
		public static function delete($id, $address_book_key) {
			if (!array_key_exists($address_book_key, self::$address_books))
				return null;

			$address_book = self::$address_books[$address_book_key];
			if ($address_book->getPermissions() & \OCP\PERMISSION_DELETE)
				return null;

			return $address_book->delete($id);
		}

		/**
		 * This function is used to create a new contact if 'id' is not given or not present.
		 * Otherwise the contact will be updated by replacing the entire data set.
		 *
		 * @param array $properties this array if key-value-pairs defines a contact
		 * @param $address_book_key string to identify the address book in which the contact shall be created or updated
		 * @return array representing the contact just created or updated
		 */
		public static function createOrUpdate($properties, $address_book_key) {

			if (!array_key_exists($address_book_key, self::$address_books))
				return null;

			$address_book = self::$address_books[$address_book_key];
			if ($address_book->getPermissions() & \OCP\PERMISSION_CREATE)
				return null;

			return $address_book->createOrUpdate($properties);
		}

		/**
		 * Check if contacts are available (e.g. contacts app enabled)
		 *
		 * @return bool true if enabled, false if not
		 */
		public static function isEnabled() {
			return !empty(self::$address_books);
		}

		/**
		 * @param \OC\IAddressBook $address_book
		 */
		public static function registerAddressBook(\OC\IAddressBook $address_book) {
			self::$address_books[$address_book->getKey()] = $address_book;
		}

		/**
		 * @param \OC\IAddressBook $address_book
		 */
		public static function unregisterAddressBook(\OC\IAddressBook $address_book) {
			unset(self::$address_books[$address_book->getKey()]);
		}

		/**
		 * @return array
		 */
		public static function getAddressBooks() {
			$result = array();
			foreach(self::$address_books as $address_book) {
				$result[$address_book->getKey()] = $address_book->getDisplayName();
			}

			return $result;
		}

		/**
		 * removes all registered address book instances
		 */
		public static function clear() {
			self::$address_books = array();
		}

		/**
		 * @var \OC\IAddressBook[] which holds all registered address books
		 */
		private static $address_books = array();
	}
}
