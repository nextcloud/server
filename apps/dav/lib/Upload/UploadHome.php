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
use Sabre\DAV\ICollection;

class UploadHome implements ICollection {
	private ?Folder $uploadFolder = null;

	public function __construct(
		private readonly array $principalInfo,
		private readonly CleanupService $cleanupService,
		private readonly IRootFolder $rootFolder,
		private readonly IUserSession $userSession,
	) {
	}

	public function createFile($name, $data = null) {
		throw new Forbidden('Permission denied to create file (filename ' . $name . ')');
	}

	public function createDirectory($name) {
		$this->impl()->createDirectory($name);

		// Add a cleanup job
		$this->cleanupService->addJob($name);
	}

	public function getChild($name): UploadFolder {
		return new UploadFolder($this->impl()->getChild($name), $this->cleanupService, $this->getStorage());
	}

	public function getChildren(): array {
		return array_map(function ($node) {
			return new UploadFolder($node, $this->cleanupService, $this->getStorage());
		}, $this->impl()->getChildren());
	}

	public function childExists($name): bool {
		return !is_null($this->getChild($name));
	}

	public function delete() {
		$this->impl()->delete();
	}

	public function getName() {
		[,$name] = \Sabre\Uri\split($this->principalInfo['uri']);
		return $name;
	}

	public function setName($name) {
		throw new Forbidden('Permission denied to rename this folder');
	}

	public function getLastModified() {
		return $this->impl()->getLastModified();
	}

	private function getUploadFolder(): Folder {
		if ($this->uploadFolder === null) {
			$user = $this->userSession->getUser();
			if (!$user) {
				throw new Forbidden('Not logged in');
			}
			$path = '/' . $user->getUID() . '/uploads';
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
