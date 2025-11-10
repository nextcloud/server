<?php

/**
 * SPDX-FileCopyrightText: 2016-2025 Nextcloud GmbH and Nextcloud contributors
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
use OCP\Share\Exceptions\ShareNotFound;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\ICollection;

/**
 * Handles DAV upload home directory operations for authenticated users or share tokens.
 * Validates principal type and ensures secure routing of upload actions.
 *
 * Instantiation may throw if the principal is unsupported or authentication is missing.
 *
 * Example URL-to-URI mapping:
 *   External: /remote.php/dav/uploads/alice/         --> Internal: /principals/users/alice
 *   External: /remote.php/dav/uploads/token_xyz/     --> Internal: /principals/shares/token_xyz
 */
class UploadHome implements ICollection {
	private string $uid;
	private ?Folder $uploadFolder = null;

	/**
	 * @throws NotFound  If the principal is unsupported/invalid or the share token is not found or invalid
	 * @throws Forbidden If the session is unauthenticated
	 */
	public function __construct(
		private readonly array $principalInfo,
		private readonly CleanupService $cleanupService,
		private readonly IRootFolder $rootFolder,
		private readonly IUserSession $userSession,
		private readonly \OCP\Share\IManager $shareManager,
	) {
		[$prefix, $name] = \Sabre\Uri\split($principalInfo['uri']);

		// Validate required components of the principal
		if (empty($name) || !in_array($prefix, ['principals/users', 'principals/shares'], true)) {
			throw new NotFound('Invalid or unsupported principal URI');
		}

		// Handle user principal
		if ($prefix === 'principals/users') {
			// Authenticated user principal: $name provided in URI is ignored, but must have a valid logged-in session.
			$user = $this->userSession->getUser();
			if (!$user) {
				throw new Forbidden('Not logged in');
			}
			// TODO: (non-security just general robustness/expected behavior): Compare the URI $name to $user/$uid?
			$this->uid = $user->getUID();
			return;
		}

		// Handle share principal
		if ($prefix === 'principals/shares') {
			// Share principal: use share token ($name) from URI to determine owner UID.
			try {
				$share = $this->shareManager->getShareByToken($name);
				$owner = $share->getShareOwner();
				if (empty($owner) || !is_string($owner)) {
					throw new NotFound('Share token not found or invalid');
				}
				$this->uid = $owner;
				return;
			} catch (ShareNotFound $e) {
				throw new NotFound('Share token not found or invalid');
			}
		}
	}

	public function createFile($name, $data = null) {
		throw new Forbidden('Permission denied to create file (filename ' . $name . ')');
	}

	public function createDirectory($name) {
		$this->getUploadDirectory()->createDirectory($name);

		// Add a cleanup job
		$this->cleanupService->addJob($this->uid, $name);
	}

	public function getChild($name): UploadFolder {
		return new UploadFolder(
			$this->getUploadDirectory()->getChild($name),
			$this->cleanupService,
			$this->getStorage(),
			$this->uid
		);
	}

	public function getChildren(): array {
		return array_map(function ($node) {
			return new UploadFolder(
				$node,
				$this->cleanupService,
				$this->getStorage(),
				$this->uid
			);
		}, $this->getUploadDirectory()->getChildren());
	}

	public function childExists($name): bool {
		try {
			return $this->getUploadDirectory()->childExists($name);
		} catch (\Throwable $e) {
			return false;
		}
	}

	public function delete() {
		$this->getUploadDirectory()->delete();
	}

	public function getName() {
		[,$name] = \Sabre\Uri\split($this->principalInfo['uri']);
		return $name;
	}

	public function setName($name) {
		throw new Forbidden('Permission denied to rename this folder');
	}

	public function getLastModified() {
		return $this->getUploadDirectory()->getLastModified();
	}

	/**
	 * Returns the Nextcloud Folder object representing the effective user's uploads folder, creating it if missing.
	 * Lazily fetches and caches the result for repeated use.
	 *
	 * @return Folder The Nextcloud Folder instance representing the user's uploads directory.
	 * @throws \Exception If the uploads path exists as a file instead of a folder.
	 *
	 */
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

	/**
	 * Returns the SabreDAV Directory node representing the effective user's uploads folder, creating it if missing.
	 *
	 * @return OCA\DAV\Connector\Sabre\Directory
	 */
	private function getUploadDirectory(): Directory {
		$folder = $this->getUploadFolder();
		$view = new View($folder->getPath());
		return new Directory($view, $folder);
	}

	/**
	 * Returns the Nextcloud storage backend for the effective user's uploads folder.
	 */
	private function getStorage(): \OCP\Files\Storage\IStorage {
		return $this->getUploadFolder()->getStorage();
	}
}
