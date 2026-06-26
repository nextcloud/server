<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_Trashbin\Sabre;

use OCA\Files_Trashbin\Trash\ITrashManager;
use OCP\IUser;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\ICollection;

class TrashHome implements ICollection {
	public function __construct(
		private array $principalInfo,
		private ITrashManager $trashManager,
		private IUser $user,
	) {
	}

	#[\Override]
	public function delete() {
		throw new Forbidden();
	}

	#[\Override]
	public function getName(): string {
		[, $name] = \Sabre\Uri\split($this->principalInfo['uri']);
		return $name;
	}

	#[\Override]
	public function setName($name) {
		throw new Forbidden('Permission denied to rename this trashbin');
	}

	#[\Override]
	public function createFile($name, $data = null) {
		throw new Forbidden('Not allowed to create files in the trashbin');
	}

	#[\Override]
	public function createDirectory($name) {
		throw new Forbidden('Not allowed to create folders in the trashbin');
	}

	#[\Override]
	public function getChild($name) {
		if ($name === 'restore') {
			return new RestoreFolder();
		}
		if ($name === 'trash') {
			return new TrashRoot($this->user, $this->trashManager);
		}

		throw new NotFound();
	}

	#[\Override]
	public function getChildren(): array {
		return [
			new RestoreFolder(),
			new TrashRoot($this->user, $this->trashManager)
		];
	}

	#[\Override]
	public function childExists($name): bool {
		return $name === 'restore' || $name === 'trash';
	}

	#[\Override]
	public function getLastModified(): int {
		return 0;
	}
}
