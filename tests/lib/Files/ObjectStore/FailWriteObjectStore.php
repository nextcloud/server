<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2018 Robin Appelman <robin@icewind.nl>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace Test\Files\ObjectStore;

use OCP\Files\ObjectStore\IObjectStore;

class FailWriteObjectStore implements IObjectStore {
	private $objectStore;

	public function __construct(IObjectStore $objectStore) {
		$this->objectStore = $objectStore;
	}

	public function getStorageId() {
		return $this->objectStore->getStorageId();
	}

	public function readObject($urn) {
		return $this->objectStore->readObject($urn);
	}

	public function writeObject($urn, $stream, ?string $mimetype = null) {
		// emulate a failed write that didn't throw an error
		return true;
	}

	public function deleteObject($urn) {
		$this->objectStore->deleteObject($urn);
	}

	public function objectExists($urn) {
		return $this->objectStore->objectExists($urn);
	}

	public function copyObject($from, $to) {
		$this->objectStore->copyObject($from, $to);
	}
}
