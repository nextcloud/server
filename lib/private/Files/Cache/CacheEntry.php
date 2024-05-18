<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Robin Appelman <robin@icewind.nl>
 *
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OC\Files\Cache;

use OCP\Files\Cache\ICacheEntry;

/**
 * meta data for a file or folder
 */
class CacheEntry implements ICacheEntry {
	/**
	 * @var array
	 */
	private $data;

	public function __construct(array $data) {
		$this->data = $data;
	}

	public function offsetSet($offset, $value): void {
		$this->data[$offset] = $value;
	}

	public function offsetExists($offset): bool {
		return isset($this->data[$offset]);
	}

	public function offsetUnset($offset): void {
		unset($this->data[$offset]);
	}

	/**
	 * @return mixed
	 */
	#[\ReturnTypeWillChange]
	public function offsetGet($offset) {
		if (isset($this->data[$offset])) {
			return $this->data[$offset];
		} else {
			return null;
		}
	}

	public function getId() {
		return (int)$this->data['fileid'];
	}

	public function getStorageId() {
		return $this->data['storage'];
	}


	public function getPath() {
		return (string)$this->data['path'];
	}


	public function getName() {
		return $this->data['name'];
	}


	public function getMimeType() {
		return $this->data['mimetype'];
	}


	public function getMimePart() {
		return $this->data['mimepart'];
	}

	public function getSize() {
		return $this->data['size'];
	}

	public function getMTime() {
		return $this->data['mtime'];
	}

	public function getStorageMTime() {
		return $this->data['storage_mtime'];
	}

	public function getEtag() {
		return $this->data['etag'];
	}

	public function getPermissions() {
		return $this->data['permissions'];
	}

	public function isEncrypted() {
		return isset($this->data['encrypted']) && $this->data['encrypted'];
	}

	public function getMetadataEtag(): ?string {
		return $this->data['metadata_etag'] ?? null;
	}

	public function getCreationTime(): ?int {
		return $this->data['creation_time'] ?? null;
	}

	public function getUploadTime(): ?int {
		return $this->data['upload_time'] ?? null;
	}

	public function getData() {
		return $this->data;
	}

	public function __clone() {
		$this->data = array_merge([], $this->data);
	}

	public function getUnencryptedSize(): int {
		if ($this->data['encrypted'] && isset($this->data['unencrypted_size']) && $this->data['unencrypted_size'] > 0) {
			return $this->data['unencrypted_size'];
		} else {
			return $this->data['size'] ?? 0;
		}
	}
}
