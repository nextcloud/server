<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\DAV\Upload;

use OCA\DAV\Connector\Sabre\Directory;
use OCA\DAV\Connector\Sabre\Exception\FileLocked;
use OCP\Files\Storage\ILockingStorage;
use OCP\Lock\ILockingProvider;
use OCP\Lock\LockedException;
use OCP\Server;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\IFile;

/**
 * Class FutureFile
 *
 * The FutureFile is a SabreDav IFile which connects the chunked upload directory
 * with the AssemblyStream, who does the final assembly job
 *
 * @package OCA\DAV\Upload
 */
class FutureFile implements \Sabre\DAV\IFile {
	/**
	 * Suffix of the path locked while the chunks are being assembled. Nothing is
	 * stored under it, so no ordinary file operation contends for it - the same
	 * trick Directory::createFile() uses for its .upload.part lock.
	 */
	private const ASSEMBLY_LOCK_SUFFIX = '.assembly';

	/**
	 * @param Directory $root
	 * @param string $name
	 */
	public function __construct(
		private Directory $root,
		private $name,
	) {
	}

	/**
	 * Mark an assembly of these chunks as in progress, so that the upload session
	 * cannot be deleted while they are still being read.
	 *
	 * @throws FileLocked if the chunks are already being assembled
	 */
	public function lockAssembly(): void {
		try {
			$this->assemblyLock(true);
		} catch (LockedException $e) {
			throw new FileLocked($e->getMessage(), $e->getCode(), $e);
		}
	}

	public function unlockAssembly(): void {
		try {
			$this->assemblyLock(false);
		} catch (LockedException $e) {
			// releasing a lock this request holds should not fail, and there is
			// nothing left to do about it if it does
		}
	}

	/**
	 * @throws LockedException
	 */
	private function assemblyLock(bool $acquire): void {
		$info = $this->root->getFileInfo();
		$storage = $info->getStorage();
		if (!$storage->instanceOfStorage(ILockingStorage::class)) {
			return;
		}

		/** @var ILockingStorage $storage */
		$path = $info->getInternalPath() . self::ASSEMBLY_LOCK_SUFFIX;
		$provider = Server::get(ILockingProvider::class);
		if ($acquire) {
			$storage->acquireLock($path, ILockingProvider::LOCK_EXCLUSIVE, $provider);
		} else {
			$storage->releaseLock($path, ILockingProvider::LOCK_EXCLUSIVE, $provider);
		}
	}

	/**
	 * @inheritdoc
	 */
	#[\Override]
	public function put($data) {
		throw new Forbidden('Permission denied to put into this file');
	}

	/**
	 * @inheritdoc
	 */
	#[\Override]
	public function get() {
		$nodes = $this->root->getChildren();
		return AssemblyStream::wrap($nodes);
	}

	public function getPath() {
		return $this->root->getFileInfo()->getInternalPath() . '/.file';
	}

	/**
	 * @inheritdoc
	 */
	#[\Override]
	public function getContentType() {
		return 'application/octet-stream';
	}

	/**
	 * @inheritdoc
	 */
	#[\Override]
	public function getETag() {
		return $this->root->getETag();
	}

	/**
	 * @inheritdoc
	 */
	#[\Override]
	public function getSize() {
		$children = $this->root->getChildren();
		$sizes = array_map(function ($node) {
			/** @var IFile $node */
			return $node->getSize();
		}, $children);

		return array_sum($sizes);
	}

	/**
	 * @inheritdoc
	 */
	#[\Override]
	public function delete() {
		$this->root->delete();
	}

	/**
	 * @inheritdoc
	 */
	#[\Override]
	public function getName() {
		return $this->name;
	}

	/**
	 * @inheritdoc
	 */
	#[\Override]
	public function setName($name) {
		throw new Forbidden('Permission denied to rename this file');
	}

	/**
	 * @inheritdoc
	 */
	#[\Override]
	public function getLastModified() {
		return $this->root->getLastModified();
	}
}
