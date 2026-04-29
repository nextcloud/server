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
use OCP\Files\FileInfo;
use OCP\IUser;
use Sabre\DAV\Exception\Forbidden;

abstract class AbstractTrash implements ITrash {
	public function __construct(
		protected ITrashManager $trashManager,
		protected ITrashItem $data,
	) {
	}

	#[\Override]
	public function getFilename(): string {
		return $this->data->getName();
	}

	#[\Override]
	public function getDeletionTime(): int {
		return $this->data->getDeletedTime();
	}

	#[\Override]
	public function getFileId(): int {
		return $this->data->getId();
	}

	#[\Override]
	public function getFileInfo(): FileInfo {
		return $this->data;
	}

	/**
	 * @psalm-suppress ImplementedReturnTypeMismatch \Sabre\DAV\IFile::getSize signature does not support 32bit
	 * @return int|float
	 */
	#[\Override]
	public function getSize(): int|float {
		return $this->data->getSize();
	}

	public function getLastModified(): int {
		return $this->data->getMtime();
	}

	public function getContentType(): string {
		return $this->data->getMimetype();
	}

	public function getETag(): string {
		return $this->data->getEtag();
	}

	public function getName(): string {
		return $this->data->getName();
	}

	#[\Override]
	public function getOriginalLocation(): string {
		return $this->data->getOriginalLocation();
	}

	#[\Override]
	public function getTitle(): string {
		return $this->data->getTitle();
	}

	#[\Override]
	public function getDeletedBy(): ?IUser {
		return $this->data->getDeletedBy();
	}

	public function delete() {
		if (!ConfigService::getDeleteFromTrashEnabled()) {
			throw new Forbidden('Not allowed to delete items from the trash bin');
		}

		$this->trashManager->removeItem($this->data);
	}

	#[\Override]
	public function restore(): bool {
		$this->trashManager->restoreItem($this->data);
		return true;
	}
}
