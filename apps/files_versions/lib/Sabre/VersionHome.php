<?php

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_Versions\Sabre;

use OC\User\NoUserException;
use OCA\Files_Versions\Versions\IVersionManager;
use OCP\Files\IRootFolder;
use OCP\IUserManager;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\ICollection;

class VersionHome implements ICollection {

	public function __construct(
		private array $principalInfo,
		private IRootFolder $rootFolder,
		private IUserManager $userManager,
		private IVersionManager $versionManager,
	) {
	}

	private function getUser() {
		[, $name] = \Sabre\Uri\split($this->principalInfo['uri']);
		$user = $this->userManager->get($name);
		if (!$user) {
			throw new NoUserException();
		}
		return $user;
	}

	public function delete() {
		throw new Forbidden();
	}

	public function getName(): string {
		return $this->getUser()->getUID();
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
		$user = $this->getUser();

		if ($name === 'versions') {
			return new VersionRoot($user, $this->rootFolder, $this->versionManager);
		}
		if ($name === 'restore') {
			return new RestoreFolder();
		}
	}

	public function getChildren() {
		$user = $this->getUser();

		return [
			new VersionRoot($user, $this->rootFolder, $this->versionManager),
			new RestoreFolder(),
		];
	}

	public function childExists($name) {
		return $name === 'versions' || $name === 'restore';
	}

	public function getLastModified() {
		return 0;
	}
}
