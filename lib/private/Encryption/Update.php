<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OC\Encryption;

use InvalidArgumentException;
use OC\Files\View;
use OCP\Encryption\Exceptions\GenericEncryptionException;
use OCP\Files\File as OCPFile;
use OCP\Files\Folder;
use OCP\Files\NotFoundException;
use Psr\Log\LoggerInterface;

/**
 * update encrypted files, e.g. because a file was shared
 */
class Update {
	public function __construct(
		protected Util $util,
		protected Manager $encryptionManager,
		protected File $file,
		protected LoggerInterface $logger,
	) {
	}

	/**
	 * hook after file was shared
	 */
	public function postShared(OCPFile|Folder $node): void {
		$this->update($node);
	}

	/**
	 * hook after file was unshared
	 */
	public function postUnshared(OCPFile|Folder $node): void {
		$this->update($node);
	}

	/**
	 * inform encryption module that a file was restored from the trash bin,
	 * e.g. to update the encryption keys
	 */
	public function postRestore(OCPFile|Folder $node): void {
		$this->update($node);
	}

	/**
	 * inform encryption module that a file was renamed,
	 * e.g. to update the encryption keys
	 */
	public function postRename(OCPFile|Folder $source, OCPFile|Folder $target): void {
		if (dirname($source->getPath()) !== dirname($target->getPath())) {
			$this->update($target);
		}
	}

	/**
	 * get owner and path relative to data/
	 *
	 * @throws \InvalidArgumentException
	 */
	protected function getOwnerPath(OCPFile|Folder $node): string {
		$owner = $node->getOwner()?->getUID();
		if ($owner === null) {
			throw new InvalidArgumentException('No owner found for ' . $node->getId());
		}
		$view = new View('/' . $owner . '/files');
		try {
			$path = $view->getPath($node->getId());
		} catch (NotFoundException $e) {
			throw new InvalidArgumentException('No file found for ' . $node->getId(), previous:$e);
		}
		return '/' . $owner . '/files/' . $path;
	}

	/**
	 * notify encryption module about added/removed users from a file/folder
	 *
	 * @param string $path relative to data/
	 * @throws Exceptions\ModuleDoesNotExistsException
	 */
	public function update(OCPFile|Folder $node): void {
		$encryptionModule = $this->encryptionManager->getEncryptionModule();

		// if the encryption module doesn't encrypt the files on a per-user basis
		// we have nothing to do here.
		if ($encryptionModule->needDetailedAccessList() === false) {
			return;
		}

		$path = $this->getOwnerPath($node);
		// if a folder was shared, get a list of all (sub-)folders
		if ($node instanceof Folder) {
			$allFiles = $this->util->getAllFiles($path);
		} else {
			$allFiles = [$path];
		}

		foreach ($allFiles as $file) {
			$usersSharing = $this->file->getAccessList($file);
			try {
				$encryptionModule->update($file, '', $usersSharing);
			} catch (GenericEncryptionException $e) {
				// If the update of an individual file fails e.g. due to a corrupt key we should continue the operation and just log the failure
				$this->logger->error('Failed to update encryption module for ' . $file, [ 'exception' => $e ]);
			}
		}
	}
}
