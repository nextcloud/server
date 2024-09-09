<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Files\Node;

use OC\Files\Filesystem;
use OC\Files\Utils\PathHelper;
use OCP\Constants;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\Mount\IMountPoint;
use OCP\Files\NotPermittedException;

/**
 * Class LazyFolder
 *
 * This is a lazy wrapper around a folder. So only
 * once it is needed this will get initialized.
 *
 * @package OC\Files\Node
 */
class LazyFolder implements Folder {
	/** @var \Closure(): Folder */
	private \Closure $folderClosure;
	protected ?Folder $folder = null;
	protected IRootFolder $rootFolder;
	protected array $data;

	/**
	 * @param IRootFolder $rootFolder
	 * @param \Closure(): Folder $folderClosure
	 * @param array $data
	 */
	public function __construct(IRootFolder $rootFolder, \Closure $folderClosure, array $data = []) {
		$this->rootFolder = $rootFolder;
		$this->folderClosure = $folderClosure;
		$this->data = $data;
	}

	protected function getRootFolder(): IRootFolder {
		return $this->rootFolder;
	}

	protected function getRealFolder(): Folder {
		if ($this->folder === null) {
			$this->folder = call_user_func($this->folderClosure);
		}
		return $this->folder;
	}

	/**
	 * Magic method to first get the real rootFolder and then
	 * call $method with $args on it
	 *
	 * @param $method
	 * @param $args
	 * @return mixed
	 */
	public function __call($method, $args) {
		return call_user_func_array([$this->getRealFolder(), $method], $args);
	}

	/**
	 * @inheritDoc
	 */
	public function getUser() {
		return $this->__call(__FUNCTION__, func_get_args());
	}

	/**
	 * @inheritDoc
	 */
	public function listen($scope, $method, callable $callback) {
		$this->__call(__FUNCTION__, func_get_args());
	}

	/**
	 * @inheritDoc
	 */
	public function removeListener($scope = null, $method = null, ?callable $callback = null) {
		$this->__call(__FUNCTION__, func_get_args());
	}

	/**
	 * @inheritDoc
	 */
	public function emit($scope, $method, $arguments = []) {
		$this->__call(__FUNCTION__, func_get_args());
	}

	/**
	 * @inheritDoc
	 */
	public function mount($storage, $mountPoint, $arguments = []) {
		$this->__call(__FUNCTION__, func_get_args());
	}

	/**
	 * @inheritDoc
	 */
	public function getMount(string $mountPoint): IMountPoint {
		return $this->__call(__FUNCTION__, func_get_args());
	}

	/**
	 * @return IMountPoint[]
	 */
	public function getMountsIn(string $mountPoint): array {
		return $this->__call(__FUNCTION__, func_get_args());
	}

	/**
	 * @inheritDoc
	 */
	public function getMountByStorageId($storageId) {
		return $this->__call(__FUNCTION__, func_get_args());
	}

	/**
	 * @inheritDoc
	 */
	public function getMountByNumericStorageId($numericId) {
		return $this->__call(__FUNCTION__, func_get_args());
	}

	/**
	 * @inheritDoc
	 */
	public function unMount($mount) {
		$this->__call(__FUNCTION__, func_get_args());
	}

	public function get($path) {
		return $this->getRootFolder()->get($this->getFullPath($path));
	}

	/**
	 * @inheritDoc
	 */
	public function rename($targetPath) {
		return $this->__call(__FUNCTION__, func_get_args());
	}

	public function delete() {
		return $this->__call(__FUNCTION__, func_get_args());
	}

	public function copy($targetPath) {
		return $this->__call(__FUNCTION__, func_get_args());
	}

	public function touch($mtime = null) {
		$this->__call(__FUNCTION__, func_get_args());
	}

	public function getStorage() {
		return $this->__call(__FUNCTION__, func_get_args());
	}

	public function getPath() {
		if (isset($this->data['path'])) {
			return $this->data['path'];
		}
		return $this->__call(__FUNCTION__, func_get_args());
	}

	public function getInternalPath() {
		return $this->__call(__FUNCTION__, func_get_args());
	}

	public function getId() {
		if (isset($this->data['fileid'])) {
			return $this->data['fileid'];
		}
		return $this->__call(__FUNCTION__, func_get_args());
	}

	public function stat() {
		return $this->__call(__FUNCTION__, func_get_args());
	}

	public function getMTime() {
		if (isset($this->data['mtime'])) {
			return $this->data['mtime'];
		}
		return $this->__call(__FUNCTION__, func_get_args());
	}

	public function getSize($includeMounts = true): int|float {
		if (isset($this->data['size'])) {
			return $this->data['size'];
		}
		return $this->__call(__FUNCTION__, func_get_args());
	}

	public function getEtag() {
		if (isset($this->data['etag'])) {
			return $this->data['etag'];
		}
		return $this->__call(__FUNCTION__, func_get_args());
	}

	public function getPermissions() {
		if (isset($this->data['permissions'])) {
			return $this->data['permissions'];
		}
		return $this->__call(__FUNCTION__, func_get_args());
	}

	public function isReadable() {
		if (isset($this->data['permissions'])) {
			return ($this->data['permissions'] & Constants::PERMISSION_READ) == Constants::PERMISSION_READ;
		}
		return $this->__call(__FUNCTION__, func_get_args());
	}

	public function isUpdateable() {
		if (isset($this->data['permissions'])) {
			return ($this->data['permissions'] & Constants::PERMISSION_UPDATE) == Constants::PERMISSION_UPDATE;
		}
		return $this->__call(__FUNCTION__, func_get_args());
	}

	public function isDeletable() {
		if (isset($this->data['permissions'])) {
			return ($this->data['permissions'] & Constants::PERMISSION_DELETE) == Constants::PERMISSION_DELETE;
		}
		return $this->__call(__FUNCTION__, func_get_args());
	}

	public function isShareable() {
		if (isset($this->data['permissions'])) {
			return ($this->data['permissions'] & Constants::PERMISSION_SHARE) == Constants::PERMISSION_SHARE;
		}
		return $this->__call(__FUNCTION__, func_get_args());
	}

	public function getParent() {
		return $this->__call(__FUNCTION__, func_get_args());
	}

	public function getName() {
		if (isset($this->data['path'])) {
			return basename($this->data['path']);
		}
		if (isset($this->data['name'])) {
			return $this->data['name'];
		}
		return $this->__call(__FUNCTION__, func_get_args());
	}

	/**
	 * @inheritDoc
	 */
	public function getUserFolder($userId) {
		return $this->__call(__FUNCTION__, func_get_args());
	}

	public function getMimetype() {
		if (isset($this->data['mimetype'])) {
			return $this->data['mimetype'];
		}
		return $this->__call(__FUNCTION__, func_get_args());
	}

	public function getMimePart() {
		if (isset($this->data['mimetype'])) {
			[$part,] = explode('/', $this->data['mimetype']);
			return $part;
		}
		return $this->__call(__FUNCTION__, func_get_args());
	}

	public function isEncrypted() {
		return $this->__call(__FUNCTION__, func_get_args());
	}

	public function getType() {
		if (isset($this->data['type'])) {
			return $this->data['type'];
		}
		return $this->__call(__FUNCTION__, func_get_args());
	}

	public function isShared() {
		return $this->__call(__FUNCTION__, func_get_args());
	}

	public function isMounted() {
		return $this->__call(__FUNCTION__, func_get_args());
	}

	public function getMountPoint() {
		return $this->__call(__FUNCTION__, func_get_args());
	}

	public function getOwner() {
		return $this->__call(__FUNCTION__, func_get_args());
	}

	public function getChecksum() {
		return $this->__call(__FUNCTION__, func_get_args());
	}

	public function getExtension(): string {
		return $this->__call(__FUNCTION__, func_get_args());
	}

	public function getFullPath($path) {
		if (isset($this->data['path'])) {
			$path = PathHelper::normalizePath($path);
			if (!Filesystem::isValidPath($path)) {
				throw new NotPermittedException('Invalid path "' . $path . '"');
			}
			return $this->data['path'] . $path;
		}
		return $this->__call(__FUNCTION__, func_get_args());
	}

	public function isSubNode($node) {
		return $this->__call(__FUNCTION__, func_get_args());
	}

	public function getDirectoryListing() {
		return $this->__call(__FUNCTION__, func_get_args());
	}

	public function nodeExists($path) {
		return $this->__call(__FUNCTION__, func_get_args());
	}

	public function newFolder($path) {
		return $this->__call(__FUNCTION__, func_get_args());
	}

	public function newFile($path, $content = null) {
		return $this->__call(__FUNCTION__, func_get_args());
	}

	public function search($query) {
		return $this->__call(__FUNCTION__, func_get_args());
	}

	public function searchByMime($mimetype) {
		return $this->__call(__FUNCTION__, func_get_args());
	}

	public function searchByTag($tag, $userId) {
		return $this->__call(__FUNCTION__, func_get_args());
	}

	public function searchBySystemTag(string $tagName, string $userId, int $limit = 0, int $offset = 0) {
		return $this->__call(__FUNCTION__, func_get_args());
	}

	public function getById($id) {
		return $this->getRootFolder()->getByIdInPath((int)$id, $this->getPath());
	}

	public function getFirstNodeById(int $id): ?\OCP\Files\Node {
		return $this->getRootFolder()->getFirstNodeByIdInPath($id, $this->getPath());
	}

	public function getFreeSpace() {
		return $this->__call(__FUNCTION__, func_get_args());
	}

	public function isCreatable() {
		return $this->__call(__FUNCTION__, func_get_args());
	}

	public function getNonExistingName($name) {
		return $this->__call(__FUNCTION__, func_get_args());
	}

	public function move($targetPath) {
		return $this->__call(__FUNCTION__, func_get_args());
	}

	public function lock($type) {
		return $this->__call(__FUNCTION__, func_get_args());
	}

	public function changeLock($targetType) {
		return $this->__call(__FUNCTION__, func_get_args());
	}

	public function unlock($type) {
		return $this->__call(__FUNCTION__, func_get_args());
	}

	public function getRecent($limit, $offset = 0) {
		return $this->__call(__FUNCTION__, func_get_args());
	}

	public function getCreationTime(): int {
		return $this->__call(__FUNCTION__, func_get_args());
	}

	public function getUploadTime(): int {
		return $this->__call(__FUNCTION__, func_get_args());
	}

	public function getRelativePath($path) {
		return PathHelper::getRelativePath($this->getPath(), $path);
	}

	public function getParentId(): int {
		if (isset($this->data['parent'])) {
			return $this->data['parent'];
		}
		return $this->__call(__FUNCTION__, func_get_args());
	}

	public function getMetadata(): array {
		return $this->data['metadata'] ?? $this->__call(__FUNCTION__, func_get_args());
	}
}
