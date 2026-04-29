<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_Versions\Sabre;

use OCA\Files_Versions\Versions\IVersion;
use OCA\Files_Versions\Versions\IVersionManager;
use OCP\Files\File;
use OCP\IUser;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\ICollection;

class VersionCollection implements ICollection {

	public function __construct(
		private File $file,
		private IUser $user,
		private IVersionManager $versionManager,
	) {
	}

	#[\Override]
	public function createFile($name, $data = null) {
		throw new Forbidden();
	}

	#[\Override]
	public function createDirectory($name) {
		throw new Forbidden();
	}

	#[\Override]
	public function getChild($name) {
		/** @var VersionFile[] $versions */
		$versions = $this->getChildren();

		foreach ($versions as $version) {
			if ($version->getName() === $name) {
				return $version;
			}
		}

		throw new NotFound();
	}

	#[\Override]
	public function getChildren(): array {
		$versions = $this->versionManager->getVersionsForFile($this->user, $this->file);

		return array_map(function (IVersion $version) {
			return new VersionFile($version, $this->versionManager);
		}, $versions);
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
		throw new Forbidden();
	}

	#[\Override]
	public function getName(): string {
		return (string)$this->file->getId();
	}

	#[\Override]
	public function setName($name) {
		throw new Forbidden();
	}

	#[\Override]
	public function getLastModified(): int {
		return 0;
	}
}
