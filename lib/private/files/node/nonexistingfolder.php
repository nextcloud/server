<?php
/**
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Vincent Petry <pvince81@owncloud.com>
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

namespace OC\Files\Node;

use OCP\Files\NotFoundException;

class NonExistingFolder extends Folder {
	/**
	 * @param string $newPath
	 * @throws \OCP\Files\NotFoundException
	 */
	public function rename($newPath) {
		throw new NotFoundException();
	}

	public function delete() {
		throw new NotFoundException();
	}

	public function copy($newPath) {
		throw new NotFoundException();
	}

	public function touch($mtime = null) {
		throw new NotFoundException();
	}

	public function getId() {
		throw new NotFoundException();
	}

	public function stat() {
		throw new NotFoundException();
	}

	public function getMTime() {
		throw new NotFoundException();
	}

	public function getSize() {
		throw new NotFoundException();
	}

	public function getEtag() {
		throw new NotFoundException();
	}

	public function getPermissions() {
		throw new NotFoundException();
	}

	public function isReadable() {
		throw new NotFoundException();
	}

	public function isUpdateable() {
		throw new NotFoundException();
	}

	public function isDeletable() {
		throw new NotFoundException();
	}

	public function isShareable() {
		throw new NotFoundException();
	}

	public function get($path) {
		throw new NotFoundException();
	}

	public function getDirectoryListing() {
		throw new NotFoundException();
	}

	public function nodeExists($path) {
		return false;
	}

	public function newFolder($path) {
		throw new NotFoundException();
	}

	public function newFile($path) {
		throw new NotFoundException();
	}

	public function search($pattern) {
		throw new NotFoundException();
	}

	public function searchByMime($mime) {
		throw new NotFoundException();
	}

	public function searchByTag($tag, $userId) {
		throw new NotFoundException();
	}

	public function getById($id) {
		throw new NotFoundException();
	}

	public function getFreeSpace() {
		throw new NotFoundException();
	}

	public function isCreatable() {
		throw new NotFoundException();
	}
}
