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
 * IAddressBook interface
 */

// use OCP namespace for all classes that are considered public.
// This means that they should be used by apps instead of the internal ownCloud classes
namespace OCP {
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
		 * @param string $pattern which should match within the $searchProperties
		 * @param array $searchProperties defines the properties within the query pattern should match
		 * @param array $options - for future use. One should always have options!
		 * @return array an array of contacts which are arrays of key-value-pairs
		 */
		public function search($pattern, $searchProperties, $options);
		//	// dummy results
		//	return array(
		//		array('id' => 0, 'FN' => 'Thomas Müller', 'EMAIL' => 'a@b.c', 'GEO' => '37.386013;-122.082932'),
		//		array('id' => 5, 'FN' => 'Thomas Tanghus', 'EMAIL' => array('d@e.f', 'g@h.i')),
		//	);

		/**
		 * @param array $properties this array if key-value-pairs defines a contact
		 * @return array an array representing the contact just created or updated
		 */
		public function createOrUpdate($properties);
		//	// dummy
		//	return array('id'    => 0, 'FN' => 'Thomas Müller', 'EMAIL' => 'a@b.c',
		//		     'PHOTO' => 'VALUE=uri:http://www.abc.com/pub/photos/jqpublic.gif',
		//		     'ADR'   => ';;123 Main Street;Any Town;CA;91921-1234'
		//	);

		/**
		 * @return mixed
		 */
		public function getPermissions();

		/**
		 * @param object $id the unique identifier to a contact
		 * @return bool successful or not
		 */
		public function delete($id);
	}
}
