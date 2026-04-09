<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_Trashbin\Sabre;

use OCA\Files_Trashbin\Trash\ITrashItem;
use OCP\Files\FileInfo;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\ICollection;

abstract class AbstractTrashFolder extends AbstractTrash implements ICollection, ITrash {
	public function getChildren(): array {
		$entries = $this->trashManager->listTrashFolder($this->data);

		$children = array_map(function (ITrashItem $entry) {
			if ($entry->getType() === FileInfo::TYPE_FOLDER) {
				return new TrashFolderFolder($this->trashManager, $entry);
			}
			return new TrashFolderFile($this->trashManager, $entry);
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

	public function setName($name) {
		throw new Forbidden();
	}

	public function createFile($name, $data = null) {
		throw new Forbidden();
	}

	public function createDirectory($name) {
		throw new Forbidden();
	}
}
