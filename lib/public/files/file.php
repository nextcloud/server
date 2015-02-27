<?php
/**
 * ownCloud
 *
 * @author Robin Appelman
 * @copyright 2013 Robin Appelman icewind@owncloud.com
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
 * Files/File interface
 */

// use OCP namespace for all classes that are considered public.
// This means that they should be used by apps instead of the internal ownCloud classes
namespace OCP\Files;

interface File extends Node {
	/**
	 * Get the content of the file as string
	 *
	 * @return string
	 * @throws \OCP\Files\NotPermittedException
	 */
	public function getContent();

	/**
	 * Write to the file from string data
	 *
	 * @param string $data
	 * @throws \OCP\Files\NotPermittedException
	 * @return void
	 */
	public function putContent($data);

	/**
	 * Get the mimetype of the file
	 *
	 * @return string
	 */
	public function getMimeType();

	/**
	 * Open the file as stream, resulting resource can be operated as stream like the result from php's own fopen
	 *
	 * @param string $mode
	 * @return resource
	 * @throws \OCP\Files\NotPermittedException
	 */
	public function fopen($mode);

	/**
	 * Compute the hash of the file
	 * Type of hash is set with $type and can be anything supported by php's hash_file
	 *
	 * @param string $type
	 * @param bool $raw
	 * @return string
	 */
	public function hash($type, $raw = false);
}
