<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Upload;

use OC\Files\View;
use OCA\DAV\Connector\Sabre\Directory;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\IUserSession;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Exception\MethodNotAllowed;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\ICollection;

class UploadHome implements ICollection {
	private string $uid;
	private ?Folder $uploadFolder = null;

	public function __construct(
		private readonly array $principalInfo,
		private readonly CleanupService $cleanupService,
		private readonly IRootFolder $rootFolder,
		private readonly IUserSession $userSession,
		private readonly \OCP\Share\IManager $shareManager,
	) {
		[$prefix, $name] = \Sabre\Uri\split($principalInfo['uri']);
		if ($prefix === 'principals/shares') {
			$this->uid = $this->shareManager->getShareByToken($name)->getShareOwner();
		} else {
			$user = $this->userSession->getUser();
			if (!$user) {
				throw new Forbidden('Not logged in');
			}

			$this->uid = $user->getUID();
		}
	}

	#[\Override]
	public function createFile($name, $data = null) {
		throw new Forbidden('Permission denied to create file (filename ' . $name . ')');
	}

	#[\Override]
	public function createDirectory($name) {
		$this->impl()->createDirectory($name);

		// Add a cleanup job
		$this->cleanupService->addJob($this->uid, $name);
	}

	#[\Override]
	public function getChild($name): UploadFolder {
		return new UploadFolder(
			$this->impl()->getChild($name),
			$this->cleanupService,
			$this->getStorage(),
			$this->uid,
		);
	}

	#[\Override]
	public function getChildren(): array {
		throw new MethodNotAllowed('Listing members of this collection is disabled');
	}

	#[\Override]
	public function childExists($name): bool {
		try {
			$this->getChild($name);
			return true;
		} catch (NotFound $e) {
			return false;
		}
	}

	#[\Override]
	public function delete() {
		$this->impl()->delete();
	}

	#[\Override]
	public function getName() {
		[,$name] = \Sabre\Uri\split($this->principalInfo['uri']);
		return $name;
	}

	#[\Override]
	public function setName($name) {
		throw new Forbidden('Permission denied to rename this folder');
	}

	#[\Override]
	public function getLastModified() {
		return $this->impl()->getLastModified();
	}

	private function getUploadFolder(): Folder {
		if ($this->uploadFolder === null) {
			$path = '/' . $this->uid . '/uploads';
			try {
				$folder = $this->rootFolder->get($path);
				if (!$folder instanceof Folder) {
					throw new \Exception('Upload folder is a file');
				}
				$this->uploadFolder = $folder;
			} catch (NotFoundException $e) {
				$this->uploadFolder = $this->rootFolder->newFolder($path);
			}
		}
		return $this->uploadFolder;
	}

	private function impl(): Directory {
		$folder = $this->getUploadFolder();
		$view = new View($folder->getPath());
		return new Directory($view, $folder);
	}

	private function getStorage() {
		return $this->getUploadFolder()->getStorage();
	}
}
