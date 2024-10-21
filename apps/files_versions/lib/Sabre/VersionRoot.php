<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_Versions\Sabre;

use OCA\Files_Versions\Versions\IVersionManager;
use OCP\Files\File;
use OCP\Files\IRootFolder;
use OCP\IUser;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\ICollection;

class VersionRoot implements ICollection {

	public function __construct(
		private IUser $user,
		private IRootFolder $rootFolder,
		private IVersionManager $versionManager,
	) {
	}

	public function delete() {
		throw new Forbidden();
	}

	public function getName(): string {
		return 'versions';
	}

	public function setName($name) {
		throw new Forbidden();
	}

	public function createFile($name, $data = null) {
		throw new Forbidden();
	}

	public function createDirectory($name) {
		throw new Forbidden();
	}

	public function getChild($name) {
		$userFolder = $this->rootFolder->getUserFolder($this->user->getUID());

		$fileId = (int)$name;
		$node = $userFolder->getFirstNodeById($fileId);

		if (!$node) {
			throw new NotFound();
		}

		if (!$node instanceof File) {
			throw new NotFound();
		}

		return new VersionCollection($node, $this->user, $this->versionManager);
	}

	public function getChildren(): array {
		return [];
	}

	public function childExists($name): bool {
		try {
			$this->getChild($name);
			return true;
		} catch (NotFound $e) {
			return false;
		}
	}

	public function getLastModified(): int {
		return 0;
	}
}
