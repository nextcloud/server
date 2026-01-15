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
use OCP\Files\Search\ISearchQuery;
use OCP\Files\Storage\IStorage;
use OCP\IUser;
use Override;

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

	#[Override]
	public function get(string $path): \OCP\Files\Node {
		return $this->getRootFolder()->get($this->getFullPath($path));
	}

	#[Override]
	public function getOrCreateFolder(string $path, int $maxRetries = 5): Folder {
		return $this->getRootFolder()->getOrCreateFolder($this->getFullPath($path), $maxRetries);
	}

	#[Override]
	public function move(string $targetPath): \OCP\Files\Node {
		return $this->__call(__FUNCTION__, func_get_args());
	}

	#[Override]
	public function delete(): void {
		$this->__call(__FUNCTION__, func_get_args());
	}

	#[Override]
	public function copy(string $targetPath): \OCP\Files\Node {
		return $this->__call(__FUNCTION__, func_get_args());
	}

	#[Override]
	public function touch(?int $mtime = null): void {
		$this->__call(__FUNCTION__, func_get_args());
	}

	#[Override]
	public function getStorage(): IStorage {
		return $this->__call(__FUNCTION__, func_get_args());
	}

	#[Override]
	public function getPath(): string {
		if (isset($this->data['path'])) {
			return $this->data['path'];
		}
		return $this->__call(__FUNCTION__, func_get_args());
	}

	#[Override]
	public function getInternalPath(): string {
		return $this->__call(__FUNCTION__, func_get_args());
	}

	#[Override]
	public function getId(): int {
		if (isset($this->data['fileid'])) {
			return $this->data['fileid'];
		}
		return $this->__call(__FUNCTION__, func_get_args());
	}

	#[Override]
	public function stat(): array {
		return $this->__call(__FUNCTION__, func_get_args());
	}

	#[Override]
	public function getMTime(): int {
		if (isset($this->data['mtime'])) {
			return $this->data['mtime'];
		}
		return $this->__call(__FUNCTION__, func_get_args());
	}

	#[Override]
	public function getSize(bool $includeMounts = true): int|float {
		if (isset($this->data['size'])) {
			return $this->data['size'];
		}
		return $this->__call(__FUNCTION__, func_get_args());
	}

	#[Override]
	public function getEtag(): string {
		if (isset($this->data['etag'])) {
			return $this->data['etag'];
		}
		return $this->__call(__FUNCTION__, func_get_args());
	}

	#[Override]
	public function getPermissions(): int {
		if (isset($this->data['permissions'])) {
			return $this->data['permissions'];
		}
		return $this->__call(__FUNCTION__, func_get_args());
	}

	#[Override]
	public function isReadable(): bool {
		if (isset($this->data['permissions'])) {
			return ($this->data['permissions'] & Constants::PERMISSION_READ) == Constants::PERMISSION_READ;
		}
		return $this->__call(__FUNCTION__, func_get_args());
	}

	#[Override]
	public function isUpdateable(): bool {
		if (isset($this->data['permissions'])) {
			return ($this->data['permissions'] & Constants::PERMISSION_UPDATE) == Constants::PERMISSION_UPDATE;
		}
		return $this->__call(__FUNCTION__, func_get_args());
	}

	#[Override]
	public function isDeletable(): bool {
		if (isset($this->data['permissions'])) {
			return ($this->data['permissions'] & Constants::PERMISSION_DELETE) == Constants::PERMISSION_DELETE;
		}
		return $this->__call(__FUNCTION__, func_get_args());
	}

	#[Override]
	public function isShareable(): bool {
		if (isset($this->data['permissions'])) {
			return ($this->data['permissions'] & Constants::PERMISSION_SHARE) == Constants::PERMISSION_SHARE;
		}
		return $this->__call(__FUNCTION__, func_get_args());
	}

	#[Override]
	public function getParent(): IRootFolder|\OCP\Files\Folder {
		return $this->__call(__FUNCTION__, func_get_args());
	}

	#[Override]
	public function getName(): string {
		if (isset($this->data['path'])) {
			return basename($this->data['path']);
		}
		if (isset($this->data['name'])) {
			return $this->data['name'];
		}
		return $this->__call(__FUNCTION__, func_get_args());
	}

	#[Override]
	public function getMimetype(): string {
		if (isset($this->data['mimetype'])) {
			return $this->data['mimetype'];
		}
		return $this->__call(__FUNCTION__, func_get_args());
	}

	#[Override]
	public function getMimePart(): string {
		if (isset($this->data['mimetype'])) {
			[$part,] = explode('/', $this->data['mimetype']);
			return $part;
		}
		return $this->__call(__FUNCTION__, func_get_args());
	}

	#[Override]
	public function isEncrypted(): bool {
		return $this->__call(__FUNCTION__, func_get_args());
	}

	#[Override]
	public function getType(): string {
		if (isset($this->data['type'])) {
			return $this->data['type'];
		}
		return $this->__call(__FUNCTION__, func_get_args());
	}

	#[Override]
	public function isShared(): bool {
		return $this->__call(__FUNCTION__, func_get_args());
	}

	#[Override]
	public function isMounted(): bool {
		return $this->__call(__FUNCTION__, func_get_args());
	}

	#[Override]
	public function getMountPoint(): IMountPoint {
		return $this->__call(__FUNCTION__, func_get_args());
	}

	#[Override]
	public function getOwner(): ?IUser {
		return $this->__call(__FUNCTION__, func_get_args());
	}

	#[Override]
	public function getChecksum(): string {
		return $this->__call(__FUNCTION__, func_get_args());
	}

	#[Override]
	public function getExtension(): string {
		return $this->__call(__FUNCTION__, func_get_args());
	}

	#[Override]
	public function getFullPath(string $path): string {
		if (isset($this->data['path'])) {
			$path = PathHelper::normalizePath($path);
			if (!Filesystem::isValidPath($path)) {
				throw new NotPermittedException('Invalid path "' . $path . '"');
			}
			return $this->data['path'] . $path;
		}
		return $this->__call(__FUNCTION__, func_get_args());
	}

	#[Override]
	public function isSubNode(\OCP\Files\Node $node): bool {
		return $this->__call(__FUNCTION__, func_get_args());
	}

	#[Override]
	public function getDirectoryListing(): array {
		return $this->__call(__FUNCTION__, func_get_args());
	}

	#[Override]
	public function nodeExists(string $path): bool {
		return $this->__call(__FUNCTION__, func_get_args());
	}

	#[Override]
	public function newFolder(string $path): \OCP\Files\Folder {
		return $this->__call(__FUNCTION__, func_get_args());
	}

	#[Override]
	public function newFile(string $path, $content = null): \OCP\Files\File {
		return $this->__call(__FUNCTION__, func_get_args());
	}

	#[Override]
	public function search(string|ISearchQuery $query): array {
		return $this->__call(__FUNCTION__, func_get_args());
	}

	#[Override]
	public function searchByMime(string $mimetype): array {
		return $this->__call(__FUNCTION__, func_get_args());
	}

	#[Override]
	public function searchByTag(int|string $tag, string $userId): array {
		return $this->__call(__FUNCTION__, func_get_args());
	}

	#[Override]
	public function searchBySystemTag(string $tagName, string $userId, int $limit = 0, int $offset = 0): array {
		return $this->__call(__FUNCTION__, func_get_args());
	}

	#[Override]
	public function getById(int $id): array {
		return $this->getRootFolder()->getByIdInPath($id, $this->getPath());
	}

	#[Override]
	public function getFirstNodeById(int $id): ?\OCP\Files\Node {
		return $this->getRootFolder()->getFirstNodeByIdInPath($id, $this->getPath());
	}

	#[Override]
	public function getFreeSpace(): int|float|false {
		return $this->__call(__FUNCTION__, func_get_args());
	}

	#[Override]
	public function isCreatable(): bool {
		return $this->__call(__FUNCTION__, func_get_args());
	}

	#[Override]
	public function getNonExistingName(string $name): string {
		return $this->__call(__FUNCTION__, func_get_args());
	}

	#[Override]
	public function lock(int $type): void {
		$this->__call(__FUNCTION__, func_get_args());
	}

	#[Override]
	public function changeLock(int $targetType): void {
		$this->__call(__FUNCTION__, func_get_args());
	}

	#[Override]
	public function unlock(int $type): void {
		$this->__call(__FUNCTION__, func_get_args());
	}

	#[Override]
	public function getRecent(int $limit, int $offset = 0): array {
		return $this->__call(__FUNCTION__, func_get_args());
	}

	#[Override]
	public function getCreationTime(): int {
		return $this->__call(__FUNCTION__, func_get_args());
	}

	#[Override]
	public function getUploadTime(): int {
		return $this->__call(__FUNCTION__, func_get_args());
	}

	#[Override]
	public function getRelativePath(string $path): ?string {
		return PathHelper::getRelativePath($this->getPath(), $path);
	}

	#[Override]
	public function getParentId(): int {
		if (isset($this->data['parent'])) {
			return $this->data['parent'];
		}
		return $this->__call(__FUNCTION__, func_get_args());
	}

	#[Override]
	public function getMetadata(): array {
		return $this->data['metadata'] ?? $this->__call(__FUNCTION__, func_get_args());
	}

	#[Override]
	public function verifyPath(string $fileName, $readonly = false): void {
		$this->__call(__FUNCTION__, func_get_args());
	}
}
