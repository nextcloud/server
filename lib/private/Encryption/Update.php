<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Encryption;

use InvalidArgumentException;
use OC\Files\Filesystem;
use OC\Files\Mount;
use OC\Files\View;
use OCP\Encryption\Exceptions\GenericEncryptionException;
use Psr\Log\LoggerInterface;

/**
 * update encrypted files, e.g. because a file was shared
 */
class Update {
	/** @var Util */
	protected $util;

	/** @var \OC\Files\Mount\Manager */
	protected $mountManager;

	/** @var Manager */
	protected $encryptionManager;

	/** @var string */
	protected $uid;

	/** @var File */
	protected $file;

	/** @var LoggerInterface */
	protected $logger;

	/**
	 * @param string $uid
	 */
	public function __construct(
		Util $util,
		Mount\Manager $mountManager,
		Manager $encryptionManager,
		File $file,
		LoggerInterface $logger,
		$uid,
	) {
		$this->util = $util;
		$this->mountManager = $mountManager;
		$this->encryptionManager = $encryptionManager;
		$this->file = $file;
		$this->logger = $logger;
		$this->uid = $uid;
	}

	/**
	 * hook after file was shared
	 */
	public function postShared(string $nodeType, int $nodeId): void {
		if ($this->encryptionManager->isEnabled()) {
			if ($nodeType === 'file' || $nodeType === 'folder') {
				$path = Filesystem::getPath($nodeId);
				[$owner, $ownerPath] = $this->getOwnerPath($path);
				$absPath = '/' . $owner . '/files/' . $ownerPath;
				$this->update($nodeType === 'folder', $absPath);
			}
		}
	}

	/**
	 * hook after file was unshared
	 */
	public function postUnshared(string $nodeType, int $nodeId): void {
		if ($this->encryptionManager->isEnabled()) {
			if ($nodeType === 'file' || $nodeType === 'folder') {
				$path = Filesystem::getPath($nodeId);
				[$owner, $ownerPath] = $this->getOwnerPath($path);
				$absPath = '/' . $owner . '/files/' . $ownerPath;
				$this->update($nodeType === 'folder', $absPath);
			}
		}
	}

	/**
	 * inform encryption module that a file was restored from the trash bin,
	 * e.g. to update the encryption keys
	 */
	public function postRestore(bool $directory, string $filePath): void {
		if ($this->encryptionManager->isEnabled()) {
			$path = Filesystem::normalizePath('/' . $this->uid . '/files/' . $filePath);
			$this->update($directory, $path);
		}
	}

	/**
	 * inform encryption module that a file was renamed,
	 * e.g. to update the encryption keys
	 */
	public function postRename(bool $directory, string $source, string $target): void {
		if (
			$this->encryptionManager->isEnabled() &&
			dirname($source) !== dirname($target)
		) {
			[$owner, $ownerPath] = $this->getOwnerPath($target);
			$absPath = '/' . $owner . '/files/' . $ownerPath;
			$this->update($directory, $absPath);
		}
	}

	/**
	 * get owner and path relative to data/<owner>/files
	 *
	 * @param string $path path to file for current user
	 * @return array ['owner' => $owner, 'path' => $path]
	 * @throws \InvalidArgumentException
	 */
	protected function getOwnerPath($path) {
		$info = Filesystem::getFileInfo($path);
		$owner = Filesystem::getOwner($path);
		$view = new View('/' . $owner . '/files');
		$path = $view->getPath($info->getId());
		if ($path === null) {
			throw new InvalidArgumentException('No file found for ' . $info->getId());
		}

		return [$owner, $path];
	}

	/**
	 * notify encryption module about added/removed users from a file/folder
	 *
	 * @param string $path relative to data/
	 * @throws Exceptions\ModuleDoesNotExistsException
	 */
	public function update(bool $directory, string $path): void {
		$encryptionModule = $this->encryptionManager->getEncryptionModule();

		// if the encryption module doesn't encrypt the files on a per-user basis
		// we have nothing to do here.
		if ($encryptionModule->needDetailedAccessList() === false) {
			return;
		}

		// if a folder was shared, get a list of all (sub-)folders
		if ($directory) {
			$allFiles = $this->util->getAllFiles($path);
		} else {
			$allFiles = [$path];
		}



		foreach ($allFiles as $file) {
			$usersSharing = $this->file->getAccessList($file);
			try {
				$encryptionModule->update($file, $this->uid, $usersSharing);
			} catch (GenericEncryptionException $e) {
				// If the update of an individual file fails e.g. due to a corrupt key we should continue the operation and just log the failure
				$this->logger->error('Failed to update encryption module for ' . $this->uid . ' ' . $file, [ 'exception' => $e ]);
			}
		}
	}
}
