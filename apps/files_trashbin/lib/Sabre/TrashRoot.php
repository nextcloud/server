<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_Trashbin\Sabre;

use OCA\Files_Trashbin\Service\ConfigService;
use OCA\Files_Trashbin\Trash\ITrashItem;
use OCA\Files_Trashbin\Trash\ITrashManager;
use OCA\Files_Trashbin\Trashbin;
use OCP\Files\FileInfo;
use OCP\IUser;
use RuntimeException;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\ICollection;
use Sabre\DAV\INode;

class TrashRoot implements ICollection {

	public function __construct(
		private readonly IUser $user,
		private readonly ITrashManager $trashManager,
	) {
	}

	#[\Override]
	public function delete() {
		if (!ConfigService::getDeleteFromTrashEnabled()) {
			throw new Forbidden('Not allowed to delete items from the trash bin');
		}

		Trashbin::deleteAll();
		foreach ($this->trashManager->listTrashRoot($this->user) as $trashItem) {
			$this->trashManager->removeItem($trashItem);
		}
	}

	#[\Override]
	public function getName(): string {
		return 'trash';
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
	public function getChildren(): array {
		$entries = $this->trashManager->listTrashRoot($this->user);

		return array_map($this->trashItemToTrashNode(...), $entries);
	}

	#[\Override]
	public function getChild($name): ITrash {
		$entry = $this->trashManager->getTrashRootItem($this->user, $name);

		if ($entry === null) {
			throw new NotFound();
		}

		return $this->trashItemToTrashNode($entry);
	}

	#[\Override]
	public function childExists($name): bool {
		return $this->trashManager->getTrashRootItem($this->user, $name) !== null;
	}

	#[\Override]
	public function getLastModified(): int {
		return 0;
	}

	private function trashItemToTrashNode(ITrashItem $entry): ITrash&INode {
		return match ($entry->getType()) {
			FileInfo::TYPE_FOLDER => new TrashFolder($this->trashManager, $entry),
			FileInfo::TYPE_FILE => new TrashFile($this->trashManager, $entry),
			default => throw new RuntimeException('Invalid FileInfo object'),
		};
	}
}
