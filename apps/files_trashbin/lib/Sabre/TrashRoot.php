<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_Trashbin\Sabre;

use OCA\Files_Trashbin\Trash\ITrashItem;
use OCA\Files_Trashbin\Trash\ITrashManager;
use OCP\Files\FileInfo;
use OCP\IUser;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\ICollection;

class TrashRoot implements ICollection {

	/** @var IUser */
	private $user;

	/** @var ITrashManager  */
	private $trashManager;

	public function __construct(IUser $user, ITrashManager $trashManager) {
		$this->user = $user;
		$this->trashManager = $trashManager;
	}

	public function delete() {
		\OCA\Files_Trashbin\Trashbin::deleteAll();
		foreach ($this->trashManager->listTrashRoot($this->user) as $trashItem) {
			$this->trashManager->removeItem($trashItem);
		}
	}

	public function getName(): string {
		return 'trash';
	}

	public function setName($name) {
		throw new Forbidden('Permission denied to rename this trashbin');
	}

	public function createFile($name, $data = null) {
		throw new Forbidden('Not allowed to create files in the trashbin');
	}

	public function createDirectory($name) {
		throw new Forbidden('Not allowed to create folders in the trashbin');
	}

	public function getChildren(): array {
		$entries = $this->trashManager->listTrashRoot($this->user);

		$children = array_map(function (ITrashItem $entry) {
			if ($entry->getType() === FileInfo::TYPE_FOLDER) {
				return new TrashFolder($this->trashManager, $entry);
			}
			return new TrashFile($this->trashManager, $entry);
		}, $entries);

		return $children;
	}

	public function getChild($name): ITrash {
		$entries = $this->getChildren();

		foreach ($entries as $entry) {
			if ($entry->getName() === $name) {
				return $entry;
			}
		}

		throw new NotFound();
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
