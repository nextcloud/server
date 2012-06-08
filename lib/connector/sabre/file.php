<?php

/**
 * ownCloud
 *
 * @author Jakob Sack
 * @copyright 2011 Jakob Sack kde@jakobsack.de
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

class OC_Connector_Sabre_File extends OC_Connector_Sabre_Node implements Sabre_DAV_IFile {

	/**
	 * Updates the data
	 *
	 * @param resource $data
	 * @return void
	 */
	public function put($data) {

		OC_Filesystem::file_put_contents($this->path,$data);

	}

	/**
	 * Returns the data
	 *
	 * @return string
	 */
	public function get() {

		return OC_Filesystem::fopen($this->path,'r');

	}

	/**
	 * Delete the current file
	 *
	 * @return void
	 */
	public function delete() {

		OC_Filesystem::unlink($this->path);

	}

	/**
	 * Returns the size of the node, in bytes
	 *
	 * @return int
	 */
	public function getSize() {
		$this->stat();
		return $this->stat_cache['size'];

	}

	/**
	 * Returns the ETag for a file
	 *
	 * An ETag is a unique identifier representing the current version of the file. If the file changes, the ETag MUST change.
	 * The ETag is an arbritrary string, but MUST be surrounded by double-quotes.
	 *
	 * Return null if the ETag can not effectively be determined
	 *
	 * @return mixed
	 */
	public function getETag() {

		return null;

	}

	/**
	 * Returns the mime-type for a file
	 *
	 * If null is returned, we'll assume application/octet-stream
	 *
	 * @return mixed
	 */
	public function getContentType() {

		return OC_Filesystem::getMimeType($this->path);

	}
}

