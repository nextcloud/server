<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Files;

use OC\Files\Mount\HomeMountPoint;
use OCA\Files_Sharing\External\Mount;
use OCA\Files_Sharing\ISharedMountPoint;
use OCP\Files\Cache\ICacheEntry;
use OCP\Files\Mount\IMountPoint;
use OCP\Files\Storage\IStorage;
use OCP\IUser;
use Override;

/**
 * @template-implements \ArrayAccess<string,mixed>
 */
class FileInfo implements \OCP\Files\FileInfo, \ArrayAccess {
	private array|ICacheEntry $data;
	private string $path;
	private IStorage $storage;
	private string $internalPath;
	private IMountPoint $mount;
	private ?IUser $owner;
	/** @var string[] */
	private array $childEtags = [];
	/** @var IMountPoint[] */
	private array $subMounts = [];
	private bool $subMountsUsed = false;
	/** The size of the file/folder without any sub mount */
	private int|float $rawSize = 0;

	public function __construct(string|bool $path, IStorage $storage, string $internalPath, array|ICacheEntry $data, IMountPoint $mount, ?IUser $owner = null) {
		$this->path = $path;
		$this->storage = $storage;
		$this->internalPath = $internalPath;
		$this->data = $data;
		$this->mount = $mount;
		$this->owner = $owner;
		if (isset($this->data['unencrypted_size']) && $this->data['unencrypted_size'] !== 0) {
			$this->rawSize = $this->data['unencrypted_size'];
		} else {
			$this->rawSize = $this->data['size'] ?? 0;
		}
	}

	public function offsetSet($offset, $value): void {
		if (is_null($offset)) {
			throw new \TypeError('Null offset not supported');
		}
		$this->data[$offset] = $value;
	}

	public function offsetExists($offset): bool {
		return isset($this->data[$offset]);
	}

	public function offsetUnset($offset): void {
		unset($this->data[$offset]);
	}

	public function offsetGet(mixed $offset): mixed {
		return match ($offset) {
			'type' => $this->getType(),
			'etag' => $this->getEtag(),
			'size' => $this->getSize(),
			'mtime' => $this->getMTime(),
			'permissions' => $this->getPermissions(),
			default => $this->data[$offset] ?? null,
		};
	}

	#[Override]
	public function getPath(): string {
		return $this->path;
	}

	#[Override]
	public function getStorage(): IStorage {
		return $this->storage;
	}

	#[Override]
	public function getInternalPath(): string {
		return $this->internalPath;
	}

	#[Override]
	public function getId(): int {
		return isset($this->data['fileid']) ? (int)$this->data['fileid'] : -1;
	}

	public function getMimetype(): string {
		return $this->data['mimetype'] ?? 'application/octet-stream';
	}

	#[Override]
	public function getMimePart(): string {
		return $this->data['mimepart'];
	}

	#[Override]
	public function getName(): string {
		return empty($this->data['name'])
			? basename($this->getPath())
			: $this->data['name'];
	}

	#[Override]
	public function getEtag(): string {
		$this->updateEntryFromSubMounts();
		if (count($this->childEtags) > 0) {
			$combinedEtag = $this->data['etag'] . '::' . implode('::', $this->childEtags);
			return md5($combinedEtag);
		} else {
			return $this->data['etag'] ?? '';
		}
	}

	#[Override]
	public function getSize(bool $includeMounts = true): int|float {
		if ($includeMounts) {
			$this->updateEntryFromSubMounts();

			if ($this->isEncrypted() && isset($this->data['unencrypted_size']) && $this->data['unencrypted_size'] > 0) {
				return $this->data['unencrypted_size'];
			} else {
				return isset($this->data['size']) ? 0 + $this->data['size'] : 0;
			}
		} else {
			return $this->rawSize;
		}
	}

	#[Override]
	public function getMTime(): int {
		$this->updateEntryFromSubMounts();
		return (int)$this->data['mtime'];
	}

	#[Override]
	public function isEncrypted(): bool {
		return $this->data['encrypted'] ?? false;
	}

	/**
	 * Return the current version used for the HMAC in the encryption app
	 */
	public function getEncryptedVersion(): int {
		return isset($this->data['encryptedVersion']) ? (int)$this->data['encryptedVersion'] : 1;
	}

	#[Override]
	public function getPermissions(): int {
		/** @var \OCP\Constants::PERMISSION_* $permission */
		$permission = (int)$this->data['permissions'];
		return $permission;
	}

	#[Override]
	public function getType(): string {
		if (!isset($this->data['type'])) {
			$this->data['type'] = ($this->getMimetype() === self::MIMETYPE_FOLDER) ? self::TYPE_FOLDER : self::TYPE_FILE;
		}
		return $this->data['type'];
	}

	public function getData(): array|ICacheEntry {
		return $this->data;
	}

	protected function checkPermissions(int $permissions): bool {
		return ($this->getPermissions() & $permissions) === $permissions;
	}

	#[Override]
	public function isReadable(): bool {
		return $this->checkPermissions(\OCP\Constants::PERMISSION_READ);
	}

	#[Override]
	public function isUpdateable(): bool {
		return $this->checkPermissions(\OCP\Constants::PERMISSION_UPDATE);
	}

	#[Override]
	public function isCreatable(): bool {
		return $this->checkPermissions(\OCP\Constants::PERMISSION_CREATE);
	}

	#[Override]
	public function isDeletable(): bool {
		return $this->checkPermissions(\OCP\Constants::PERMISSION_DELETE);
	}

	#[Override]
	public function isShareable(): bool {
		return $this->checkPermissions(\OCP\Constants::PERMISSION_SHARE);
	}

	#[Override]
	public function isShared(): bool {
		return $this->mount instanceof ISharedMountPoint;
	}

	#[Override]
	public function isMounted(): bool {
		$isHome = $this->mount instanceof HomeMountPoint;
		return !$isHome && !$this->isShared();
	}

	#[Override]
	public function getMountPoint(): IMountPoint {
		return $this->mount;
	}

	/**
	 * Get the owner of the file.
	 */
	public function getOwner(): ?IUser {
		return $this->owner;
	}

	/**
	 * @param IMountPoint[] $mounts
	 */
	public function setSubMounts(array $mounts): void {
		$this->subMounts = $mounts;
	}

	private function updateEntryFromSubMounts(): void {
		if ($this->subMountsUsed) {
			return;
		}
		$this->subMountsUsed = true;
		foreach ($this->subMounts as $mount) {
			$subStorage = $mount->getStorage();
			if ($subStorage) {
				$subCache = $subStorage->getCache('');
				$rootEntry = $subCache->get('');
				if (!empty($rootEntry)) {
					$this->addSubEntry($rootEntry, $mount->getMountPoint());
				}
			}
		}
	}

	/**
	 * Add a cache entry which is the child of this folder.
	 *
	 * Sets the size, etag and size to for cross-storage children.
	 *
	 * @param array|ICacheEntry $data cache entry for the child
	 * @param string $entryPath full path of the child entry
	 */
	public function addSubEntry(array|ICacheEntry $data, string $entryPath): void {
		if (empty($data)) {
			return;
		}
		$hasUnencryptedSize = isset($data['unencrypted_size']) && $data['unencrypted_size'] > 0;
		if ($hasUnencryptedSize) {
			$subSize = $data['unencrypted_size'];
		} else {
			$subSize = $data['size'] ?: 0;
		}
		$this->data['size'] += $subSize;
		if ($hasUnencryptedSize) {
			$this->data['unencrypted_size'] += $subSize;
		}
		if (isset($data['mtime'])) {
			$this->data['mtime'] = max($this->data['mtime'], $data['mtime']);
		}
		if (isset($data['etag'])) {
			// prefix the etag with the relative path of the subentry to propagate etag on mount moves
			$relativeEntryPath = substr($entryPath, strlen($this->getPath()));
			// attach the permissions to propagate etag on permission changes of submounts
			$permissions = isset($data['permissions']) ? $data['permissions'] : 0;
			$this->childEtags[] = $relativeEntryPath . '/' . $data['etag'] . $permissions;
		}
	}

	#[Override]
	public function getChecksum(): string {
		return $this->data['checksum'] ?? '';
	}

	#[Override]
	public function getExtension(): string {
		return pathinfo($this->getName(), PATHINFO_EXTENSION);
	}

	#[Override]
	public function getCreationTime(): int {
		return (int)$this->data['creation_time'];
	}

	#[Override]
	public function getUploadTime(): int {
		return (int)$this->data['upload_time'];
	}

	#[Override]
	public function getParentId(): int {
		return $this->data['parent'] ?? -1;
	}

	#[Override]
	public function getMetadata(): array {
		return $this->data['metadata'] ?? [];
	}
}
