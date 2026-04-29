<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Files\Cache;

use OCP\Files\Cache\ICacheEntry;

/**
 * meta data for a file or folder
 */
class CacheEntry implements ICacheEntry {
	public function __construct(
		private array $data,
	) {
	}

	#[\Override]
	public function offsetSet($offset, $value): void {
		$this->data[$offset] = $value;
	}

	#[\Override]
	public function offsetExists($offset): bool {
		return isset($this->data[$offset]);
	}

	#[\Override]
	public function offsetUnset($offset): void {
		unset($this->data[$offset]);
	}

	/**
	 * @return mixed
	 */
	#[\Override]
	#[\ReturnTypeWillChange]
	public function offsetGet($offset) {
		if (isset($this->data[$offset])) {
			return $this->data[$offset];
		} else {
			return null;
		}
	}

	#[\Override]
	public function getId() {
		return (int)$this->data['fileid'];
	}

	#[\Override]
	public function getStorageId() {
		return $this->data['storage'];
	}


	#[\Override]
	public function getPath() {
		return (string)$this->data['path'];
	}


	#[\Override]
	public function getName() {
		return $this->data['name'];
	}


	#[\Override]
	public function getMimeType(): string {
		return $this->data['mimetype'] ?? 'application/octet-stream';
	}


	#[\Override]
	public function getMimePart() {
		return $this->data['mimepart'];
	}

	#[\Override]
	public function getSize() {
		return $this->data['size'];
	}

	#[\Override]
	public function getMTime() {
		return $this->data['mtime'];
	}

	#[\Override]
	public function getStorageMTime() {
		return $this->data['storage_mtime'];
	}

	#[\Override]
	public function getEtag() {
		return $this->data['etag'];
	}

	#[\Override]
	public function getPermissions(): int {
		return $this->data['permissions'];
	}

	#[\Override]
	public function isEncrypted() {
		return isset($this->data['encrypted']) && $this->data['encrypted'];
	}

	#[\Override]
	public function getMetadataEtag(): ?string {
		return $this->data['metadata_etag'] ?? null;
	}

	#[\Override]
	public function getCreationTime(): ?int {
		return $this->data['creation_time'] ?? null;
	}

	#[\Override]
	public function getUploadTime(): ?int {
		return $this->data['upload_time'] ?? null;
	}

	#[\Override]
	public function getParentId(): int {
		return $this->data['parent'];
	}

	public function getData() {
		return $this->data;
	}

	public function __clone() {
		$this->data = array_merge([], $this->data);
	}

	#[\Override]
	public function getUnencryptedSize(): int {
		if ($this->data['encrypted'] && isset($this->data['unencrypted_size']) && $this->data['unencrypted_size'] > 0) {
			return $this->data['unencrypted_size'];
		} else {
			return $this->data['size'] ?? 0;
		}
	}
}
