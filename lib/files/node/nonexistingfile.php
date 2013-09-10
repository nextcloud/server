<?php
/**
 * Copyright (c) 2013 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Files\Node;

use OCP\Files\NotFoundException;

class NonExistingFile extends File {
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

	public function getContent() {
		throw new NotFoundException();
	}

	public function putContent($data) {
		throw new NotFoundException();
	}

	public function getMimeType() {
		throw new NotFoundException();
	}

	public function fopen($mode) {
		throw new NotFoundException();
	}
}
