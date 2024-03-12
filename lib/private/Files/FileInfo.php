<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Piotr M <mrow4a@yahoo.com>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author tbartenstein <tbartenstein@users.noreply.github.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Vincent Petry <vincent@nextcloud.com>
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
namespace OC\Files;

use OC\Files\Mount\HomeMountPoint;
use OCA\Files_Sharing\External\Mount;
use OCA\Files_Sharing\ISharedMountPoint;
use OCP\Files\Cache\ICacheEntry;
use OCP\Files\Mount\IMountPoint;
use OCP\IUser;

class FileInfo implements \OCP\Files\FileInfo, \ArrayAccess {
	private array|ICacheEntry $data;
	/**
	 * @var string
	 */
	private $path;

	/**
	 * @var \OC\Files\Storage\Storage $storage
	 */
	private $storage;

	/**
	 * @var string
	 */
	private $internalPath;

	/**
	 * @var \OCP\Files\Mount\IMountPoint
	 */
	private $mount;

	private ?IUser $owner;

	/**
	 * @var string[]
	 */
	private array $childEtags = [];

	/**
	 * @var IMountPoint[]
	 */
	private array $subMounts = [];

	private bool $subMountsUsed = false;

	/**
	 * The size of the file/folder without any sub mount
	 */
	private int|float $rawSize = 0;

	/**
	 * @param string|boolean $path
	 * @param Storage\Storage $storage
	 * @param string $internalPath
	 * @param array|ICacheEntry $data
	 * @param IMountPoint $mount
	 * @param ?IUser $owner
	 */
	public function __construct($path, $storage, $internalPath, $data, $mount, $owner = null) {
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

	/**
	 * @return mixed
	 */
	#[\ReturnTypeWillChange]
	public function offsetGet($offset) {
		return match ($offset) {
			'type' => $this->getType(),
			'etag' => $this->getEtag(),
			'size' => $this->getSize(),
			'mtime' => $this->getMTime(),
			'permissions' => $this->getPermissions(),
			default => $this->data[$offset] ?? null,
		};
	}

	/**
	 * @return string
	 */
	public function getPath() {
		return $this->path;
	}

	public function getStorage() {
		return $this->storage;
	}

	/**
	 * @return string
	 */
	public function getInternalPath() {
		return $this->internalPath;
	}

	/**
	 * Get FileInfo ID or null in case of part file
	 *
	 * @return int|null
	 */
	public function getId() {
		return isset($this->data['fileid']) ? (int)  $this->data['fileid'] : null;
	}

	/**
	 * @return string
	 */
	public function getMimetype() {
		return $this->data['mimetype'];
	}

	/**
	 * @return string
	 */
	public function getMimePart() {
		return $this->data['mimepart'];
	}

	/**
	 * @return string
	 */
	public function getName() {
		return empty($this->data['name'])
			? basename($this->getPath())
			: $this->data['name'];
	}

	/**
	 * @return string
	 */
	public function getEtag() {
		$this->updateEntryfromSubMounts();
		if (count($this->childEtags) > 0) {
			$combinedEtag = $this->data['etag'] . '::' . implode('::', $this->childEtags);
			return md5($combinedEtag);
		} else {
			return $this->data['etag'];
		}
	}

	/**
	 * @param bool $includeMounts
	 * @return int|float
	 */
	public function getSize($includeMounts = true) {
		if ($includeMounts) {
			$this->updateEntryfromSubMounts();

			if ($this->isEncrypted() && isset($this->data['unencrypted_size']) && $this->data['unencrypted_size'] > 0) {
				return $this->data['unencrypted_size'];
			} else {
				return isset($this->data['size']) ? 0 + $this->data['size'] : 0;
			}
		} else {
			return $this->rawSize;
		}
	}

	/**
	 * @return int
	 */
	public function getMTime() {
		$this->updateEntryfromSubMounts();
		return (int) $this->data['mtime'];
	}

	/**
	 * @return bool
	 */
	public function isEncrypted() {
		return $this->data['encrypted'] ?? false;
	}

	/**
	 * Return the current version used for the HMAC in the encryption app
	 */
	public function getEncryptedVersion(): int {
		return isset($this->data['encryptedVersion']) ? (int) $this->data['encryptedVersion'] : 1;
	}

	/**
	 * @return int
	 */
	public function getPermissions() {
		return (int) $this->data['permissions'];
	}

	/**
	 * @return string \OCP\Files\FileInfo::TYPE_FILE|\OCP\Files\FileInfo::TYPE_FOLDER
	 */
	public function getType() {
		if (!isset($this->data['type'])) {
			$this->data['type'] = ($this->getMimetype() === self::MIMETYPE_FOLDER) ? self::TYPE_FOLDER : self::TYPE_FILE;
		}
		return $this->data['type'];
	}

	public function getData() {
		return $this->data;
	}

	/**
	 * @param int $permissions
	 * @return bool
	 */
	protected function checkPermissions($permissions) {
		return ($this->getPermissions() & $permissions) === $permissions;
	}

	/**
	 * @return bool
	 */
	public function isReadable() {
		return $this->checkPermissions(\OCP\Constants::PERMISSION_READ);
	}

	/**
	 * @return bool
	 */
	public function isUpdateable() {
		return $this->checkPermissions(\OCP\Constants::PERMISSION_UPDATE);
	}

	/**
	 * Check whether new files or folders can be created inside this folder
	 *
	 * @return bool
	 */
	public function isCreatable() {
		return $this->checkPermissions(\OCP\Constants::PERMISSION_CREATE);
	}

	/**
	 * @return bool
	 */
	public function isDeletable() {
		return $this->checkPermissions(\OCP\Constants::PERMISSION_DELETE);
	}

	/**
	 * @return bool
	 */
	public function isShareable() {
		return $this->checkPermissions(\OCP\Constants::PERMISSION_SHARE);
	}

	/**
	 * Check if a file or folder is shared
	 *
	 * @return bool
	 */
	public function isShared() {
		return $this->mount instanceof ISharedMountPoint;
	}

	public function isMounted() {
		$isHome = $this->mount instanceof HomeMountPoint;
		return !$isHome && !$this->isShared();
	}

	/**
	 * Get the mountpoint the file belongs to
	 *
	 * @return \OCP\Files\Mount\IMountPoint
	 */
	public function getMountPoint() {
		return $this->mount;
	}

	/**
	 * Get the owner of the file
	 *
	 * @return ?IUser
	 */
	public function getOwner() {
		return $this->owner;
	}

	/**
	 * @param IMountPoint[] $mounts
	 */
	public function setSubMounts(array $mounts) {
		$this->subMounts = $mounts;
	}

	private function updateEntryfromSubMounts(): void {
		if ($this->subMountsUsed) {
			return;
		}
		$this->subMountsUsed = true;
		foreach ($this->subMounts as $mount) {
			$subStorage = $mount->getStorage();
			if ($subStorage) {
				$subCache = $subStorage->getCache('');
				$rootEntry = $subCache->get('');
				$this->addSubEntry($rootEntry, $mount->getMountPoint());
			}
		}
	}

	/**
	 * Add a cache entry which is the child of this folder
	 *
	 * Sets the size, etag and size to for cross-storage childs
	 *
	 * @param array|ICacheEntry $data cache entry for the child
	 * @param string $entryPath full path of the child entry
	 */
	public function addSubEntry($data, $entryPath) {
		if (!$data) {
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

	/**
	 * @inheritdoc
	 */
	public function getChecksum() {
		return $this->data['checksum'];
	}

	public function getExtension(): string {
		return pathinfo($this->getName(), PATHINFO_EXTENSION);
	}

	public function getCreationTime(): int {
		return (int) $this->data['creation_time'];
	}

	public function getUploadTime(): int {
		return (int) $this->data['upload_time'];
	}

	public function getParentId(): int {
		return $this->data['parent'] ?? -1;
	}

	/**
	 * @inheritDoc
	 * @return array<string, int|string|bool|float|string[]|int[]>
	 */
	public function getMetadata(): array {
		return $this->data['metadata'] ?? [];
	}
}
