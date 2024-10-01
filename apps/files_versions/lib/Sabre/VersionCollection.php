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

	/** @var File */
	private $file;

	/** @var IUser */
	private $user;

	/** @var IVersionManager */
	private $versionManager;

	public function __construct(File $file, IUser $user, IVersionManager $versionManager) {
		$this->file = $file;
		$this->user = $user;
		$this->versionManager = $versionManager;
	}

	public function createFile($name, $data = null) {
		throw new Forbidden();
	}

	public function createDirectory($name) {
		throw new Forbidden();
	}

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

	public function getChildren(): array {
		$versions = $this->versionManager->getVersionsForFile($this->user, $this->file);

		return array_map(function (IVersion $version) {
			return new VersionFile($version, $this->versionManager);
		}, $versions);
	}

	public function childExists($name): bool {
		try {
			$this->getChild($name);
			return true;
		} catch (NotFound $e) {
			return false;
		}
	}

	public function delete() {
		throw new Forbidden();
	}

	public function getName(): string {
		return (string)$this->file->getId();
	}

	public function setName($name) {
		throw new Forbidden();
	}

	public function getLastModified(): int {
		return 0;
	}
}
