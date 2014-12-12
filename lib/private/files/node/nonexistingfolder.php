<?php
/**
 * Copyright (c) 2013 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
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
