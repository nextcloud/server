<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
// use OCP namespace for all classes that are considered public.
// This means that they should be used by apps instead of the internal Nextcloud classes

namespace OCP {
	/**
	 * Interface IAddressBook
	 *
	 * @since 5.0.0
	 */
	interface IAddressBook {
		/**
		 * @return string defining the technical unique key
		 * @since 5.0.0
		 */
		public function getKey();

		/**
		 * @return string defining the unique uri
		 * @since 16.0.0
		 */
		public function getUri(): string;

		/**
		 * In comparison to getKey() this function returns a human readable (maybe translated) name
		 * @return mixed
		 * @since 5.0.0
		 */
		public function getDisplayName();

		/**
		 * @param string $pattern which should match within the $searchProperties
		 * @param array $searchProperties defines the properties within the query pattern should match
		 * @param array $options Options to define the output format and search behavior
		 *                       - 'types' boolean (since 15.0.0) If set to true, fields that come with a TYPE property will be an array
		 *                       example: ['id' => 5, 'FN' => 'Thomas Tanghus', 'EMAIL' => ['type => 'HOME', 'value' => 'g@h.i']]
		 *                       - 'escape_like_param' - If set to false wildcards _ and % are not escaped
		 *                       - 'limit' - Set a numeric limit for the search results
		 *                       - 'offset' - Set the offset for the limited search results
		 *                       - 'wildcard' - (since 23.0.0) Whether the search should use wildcards
		 * @psalm-param array{types?: bool, escape_like_param?: bool, limit?: int, offset?: int, wildcard?: bool} $options
		 * @return array an array of contacts which are arrays of key-value-pairs
		 *               example result:
		 *               [
		 *               ['id' => 0, 'FN' => 'Thomas Müller', 'EMAIL' => 'a@b.c', 'GEO' => '37.386013;-122.082932'],
		 *               ['id' => 5, 'FN' => 'Thomas Tanghus', 'EMAIL' => ['d@e.f', 'g@h.i']]
		 *               ]
		 * @since 5.0.0
		 */
		public function search($pattern, $searchProperties, $options);

		/**
		 * @param array $properties this array if key-value-pairs defines a contact
		 * @return array an array representing the contact just created or updated
		 * @since 5.0.0
		 */
		public function createOrUpdate($properties);
		//	// dummy
		//	return array('id'    => 0, 'FN' => 'Thomas Müller', 'EMAIL' => 'a@b.c',
		//		     'PHOTO' => 'VALUE=uri:http://www.abc.com/pub/photos/jqpublic.gif',
		//		     'ADR'   => ';;123 Main Street;Any Town;CA;91921-1234'
		//	);

		/**
		 * @return mixed
		 * @since 5.0.0
		 */
		public function getPermissions();

		/**
		 * @param int $id the unique identifier to a contact
		 * @return bool successful or not
		 * @since 5.0.0
		 */
		public function delete($id);

		/**
		 * Returns true if this address-book is not owned by the current user,
		 * but shared with them.
		 *
		 * @return bool
		 * @since 20.0.0
		 */
		public function isShared(): bool;

		/**
		 * @return bool
		 * @since 20.0.0
		 */
		public function isSystemAddressBook(): bool;
	}
}
